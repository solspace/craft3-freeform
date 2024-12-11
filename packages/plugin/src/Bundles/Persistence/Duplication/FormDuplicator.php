<?php

namespace Solspace\Freeform\Bundles\Persistence\Duplication;

use craft\db\Query;
use craft\db\Table;
use craft\helpers\StringHelper;
use craft\records\UserPermission;
use craft\records\UserPermission_User;
use craft\records\UserPermission_UserGroup;
use Solspace\Freeform\Attributes\Property\Input\Field;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMapping;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Fields\Implementations\Pro\GroupField;
use Solspace\Freeform\Form\Managers\ContentManager;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Library\Helpers\StringHelper as FreeformStringHelper;
use Solspace\Freeform\Notifications\Types\Conditional\Conditional;
use Solspace\Freeform\Notifications\Types\Dynamic\Dynamic;
use Solspace\Freeform\Notifications\Types\EmailField\EmailField;
use Solspace\Freeform\Records\Form\FormFieldRecord;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\Form\FormLayoutRecord;
use Solspace\Freeform\Records\Form\FormNotificationRecord;
use Solspace\Freeform\Records\Form\FormPageRecord;
use Solspace\Freeform\Records\Form\FormRowRecord;
use Solspace\Freeform\Records\Form\FormSiteRecord;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Records\FormTranslationRecord;
use Solspace\Freeform\Records\IntegrationRecord;
use Solspace\Freeform\Records\Rules\ButtonRuleRecord;
use Solspace\Freeform\Records\Rules\FieldRuleRecord;
use Solspace\Freeform\Records\Rules\NotificationRuleRecord;
use Solspace\Freeform\Records\Rules\PageRuleRecord;
use Solspace\Freeform\Records\Rules\RuleConditionRecord;
use Solspace\Freeform\Records\Rules\RuleRecord;
use Solspace\Freeform\Records\Rules\SubmitFormRuleRecord;
use Solspace\Freeform\Services\Form\TranslationsService;
use Solspace\Freeform\Services\FormsService;
use yii\db\ActiveRecord;

class FormDuplicator
{
    /** @var FormLayoutRecord[] */
    private array $layouts;

    /** @var FormLayoutRecord[] */
    private array $layoutsUids = [];

    /** @var FormPageRecord[] */
    private array $pages;

    /** @var FormRowRecord[] */
    private array $rows;

    /** @var FormFieldRecord[] */
    private array $fields;

    /** @var FormNotificationRecord[] */
    private array $notifications;

    /** @var FormIntegrationRecord[] */
    private array $integrations;

    private array $fieldUidMap = [];
    private array $pageUidMap = [];

    public function __construct(
        private FormsService $formsService,
        private PropertyProvider $propertyProvider,
    ) {}

    public function clone(int $id): bool
    {
        $form = $this->cloneForm($id);
        if (!$form) {
            return false;
        }

        $this->warmUpLayout($id);

        $this->cloneLayouts($form);
        $this->clonePages($form);
        $this->cloneRows($form);
        $this->cloneFields($form);
        $this->cloneTranslations($id, $form);
        $this->cloneSites($id, $form);
        $this->cloneNotifications($id, $form);
        $this->cloneRules($id, $form);
        $this->cloneIntegrations($id, $form);
        $this->updatePermissions($id, $form);

        $form = $this->formsService->getFormById($form->id);

        $contentManager = new ContentManager($form, $this->fields);
        $contentManager->performDatabaseColumnAlterations();

        return true;
    }

    private function cloneForm(int $id): ?FormRecord
    {
        $formRecord = FormRecord::findOne(['id' => $id]);
        if (!$formRecord) {
            return null;
        }

        $clone = new FormRecord();
        $clone->setAttributes($formRecord->getAttributes(), false);
        $clone->uid = StringHelper::UUID();
        $clone->id = null;
        $clone->name = FreeformStringHelper::incrementStringWithNumber($formRecord->name, true);
        $clone->handle = FreeformStringHelper::incrementStringWithNumber($formRecord->handle);

        $i = 0;
        do {
            $metadata = JsonHelper::decode($clone->metadata);
            $metadata->general->name = $clone->name;
            $metadata->general->handle = $clone->handle;
            $clone->metadata = json_encode($metadata);

            $clone->save();

            if ($clone->hasErrors('handle')) {
                $clone->name = FreeformStringHelper::incrementStringWithNumber($clone->name, true);
                $clone->handle = FreeformStringHelper::incrementStringWithNumber($clone->handle);
            }
        } while ($clone->hasErrors() || $i++ > 1000);

        if ($clone->hasErrors()) {
            return null;
        }

        return $clone;
    }

    private function cloneLayouts(FormRecord $form): void
    {
        foreach ($this->layouts as $id => $layout) {
            $clone = new FormLayoutRecord();
            $clone->setAttributes($layout->getAttributes(), false);
            $clone->uid = StringHelper::UUID();
            $clone->id = null;
            $clone->formId = $form->id;
            $clone->save();

            if ($clone->hasErrors()) {
                continue;
            }

            $this->layouts[$id] = $clone;
            $this->layoutsUids[$layout->uid] = $clone;
        }
    }

    private function clonePages(FormRecord $form): void
    {
        foreach ($this->pages as $id => $page) {
            $clone = new FormPageRecord();
            $clone->setAttributes($page->getAttributes(), false);
            $clone->uid = StringHelper::UUID();
            $clone->id = null;
            $clone->formId = $form->id;
            $clone->layoutId = $this->layouts[$page->layoutId]->id;
            $clone->save();

            if ($clone->hasErrors()) {
                continue;
            }

            $this->pages[$id] = $clone;
            $this->pageUidMap[$page->uid] = $clone->uid;
        }
    }

    private function cloneRows(FormRecord $form): void
    {
        foreach ($this->rows as $id => $row) {
            $clone = new FormRowRecord();
            $clone->setAttributes($row->getAttributes(), false);
            $clone->uid = StringHelper::UUID();
            $clone->id = null;
            $clone->formId = $form->id;
            $clone->layoutId = $this->layouts[$row->layoutId]->id;
            $clone->save();

            if ($clone->hasErrors()) {
                continue;
            }

            $this->rows[$id] = $clone;
        }
    }

    private function cloneFields(FormRecord $form): void
    {
        foreach ($this->fields as $id => $field) {
            $clone = new FormFieldRecord();
            $clone->setAttributes($field->getAttributes(), false);
            $clone->uid = StringHelper::UUID();
            $clone->id = null;
            $clone->formId = $form->id;
            $clone->rowId = $this->rows[$field->rowId]->id;

            if (GroupField::class === $clone->type) {
                $metadata = JsonHelper::decode($clone->metadata);
                $metadata->layout = $this->layoutsUids[$metadata->layout]->uid;
                $clone->metadata = json_encode($metadata);
            }

            $clone->save();

            if ($clone->hasErrors()) {
                continue;
            }

            $this->fields[$id] = $clone;
            $this->fieldUidMap[$field->uid] = $clone->uid;
        }
    }

    private function cloneSites(int $originalId, FormRecord $form): void
    {
        $siteIds = FormSiteRecord::find()->select('siteId')->where(['formId' => $originalId])->column();

        foreach ($siteIds as $siteId) {
            $siteRecord = new FormSiteRecord();
            $siteRecord->formId = $form->id;
            $siteRecord->siteId = $siteId;
            $siteRecord->save();
        }
    }

    private function cloneTranslations(int $originalId, FormRecord $form): void
    {
        $translations = FormTranslationRecord::findAll(['formId' => $originalId]);
        foreach ($translations as $translation) {
            $clone = new FormTranslationRecord();
            $clone->formId = $form->id;
            $clone->siteId = $translation->siteId;
            $clone->uid = StringHelper::UUID();

            $oldTranslations = json_decode($translation->translations, true);

            $updatedTranslations = [];
            foreach ($oldTranslations as $type => $namespaces) {
                $updatedTranslations[$type] = [];
                foreach ($namespaces as $namespace => $translations) {
                    $updatedNamespace = match ($type) {
                        TranslationsService::TYPE_PAGES => $this->pageUidMap[$namespace],
                        TranslationsService::TYPE_FIELDS => $this->fieldUidMap[$namespace],
                        default => $namespace,
                    };

                    $updatedTranslations[$type][$updatedNamespace] = [];
                    foreach ($translations as $key => $value) {
                        $updatedTranslations[$type][$updatedNamespace][$key] = $value;
                    }
                }
            }

            $clone->translations = json_encode($updatedTranslations);

            $clone->save();
        }
    }

    private function cloneNotifications(int $originalId, FormRecord $form): void
    {
        $records = FormNotificationRecord::findAll(['formId' => $originalId]);
        foreach ($records as $record) {
            $clone = new FormNotificationRecord();
            $clone->setAttributes($record->getAttributes());
            $clone->formId = $form->id;
            $clone->uid = StringHelper::UUID();
            $clone->save();

            $metadata = json_decode($clone->metadata);

            switch ($clone->class) {
                case Conditional::class:
                    /** @var NotificationRuleRecord $rule */
                    $rule = NotificationRuleRecord::find()
                        ->innerJoin(
                            RuleRecord::TABLE.' r',
                            'r.[[id]] = '.NotificationRuleRecord::TABLE.'.[[id]]'
                        )
                        ->where(['r.[[uid]]' => $metadata->rule])->one()
                    ;

                    if ($rule) {
                        $ruleClone = new NotificationRuleRecord();
                        $ruleClone->setAttributes($rule->getAttributes());
                        $ruleClone->notificationId = $clone->id;

                        $this->buildRuleClone($rule, $ruleClone);

                        $metadata->rule = $ruleClone->uid;
                    }

                    break;

                case Dynamic::class:
                case EmailField::class:
                    $metadata->field = $this->fieldUidMap[$metadata->field];

                    break;
            }

            $clone->metadata = json_encode($metadata);
            $clone->save();
        }
    }

    private function cloneRules(int $originalId, FormRecord $form): void
    {
        $records = PageRuleRecord::findAll(['pageId' => array_keys($this->pages)]);
        foreach ($records as $rule) {
            $ruleClone = new PageRuleRecord();
            $ruleClone->pageId = $this->pages[$rule->pageId]->id;

            $this->buildRuleClone($rule, $ruleClone);
        }

        $records = FieldRuleRecord::findAll(['fieldId' => array_keys($this->fields)]);
        foreach ($records as $rule) {
            $ruleClone = new FieldRuleRecord();
            $ruleClone->fieldId = $this->fields[$rule->fieldId]->id;
            $ruleClone->display = $rule->display;

            $this->buildRuleClone($rule, $ruleClone);
        }

        $records = ButtonRuleRecord::findAll(['pageId' => array_keys($this->pages)]);
        foreach ($records as $rule) {
            $ruleClone = new ButtonRuleRecord();
            $ruleClone->pageId = $this->pages[$rule->pageId]->id;
            $ruleClone->button = $rule->button;
            $ruleClone->display = $rule->display;

            $this->buildRuleClone($rule, $ruleClone);
        }

        $rule = SubmitFormRuleRecord::findOne(['formId' => $originalId]);
        if ($rule) {
            $ruleClone = new SubmitFormRuleRecord();
            $ruleClone->formId = $form->id;

            $this->buildRuleClone($rule, $ruleClone);
        }
    }

    private function cloneIntegrations(int $originalId, FormRecord $form): void
    {
        $integrationClassMap = IntegrationRecord::find()
            ->select(['class'])
            ->indexBy('id')
            ->column()
        ;

        $records = FormIntegrationRecord::findAll(['formId' => $originalId]);
        foreach ($records as $record) {
            $clone = new FormIntegrationRecord();
            $clone->setAttributes($record->getAttributes());
            $clone->formId = $form->id;
            $clone->uid = StringHelper::UUID();

            $metadata = json_decode($clone->metadata);

            $className = $integrationClassMap[$record->integrationId];
            $properties = $this->propertyProvider->getEditableProperties($className);

            foreach ($properties as $property) {
                $handle = $property->handle;
                if (!$handle || !isset($metadata->{$handle})) {
                    continue;
                }

                if ($property instanceof FieldMapping) {
                    foreach ($metadata->{$handle} as $key => $value) {
                        if ('relation' !== $value->type) {
                            continue;
                        }

                        if (!isset($this->fieldUidMap[$value->value])) {
                            continue;
                        }

                        if (!isset($metadata->{$handle}->{$key}) || !\is_object($metadata->{$handle}->{$key})) {
                            continue;
                        }

                        if (!isset($metadata->{$handle}->{$key}->value)) {
                            continue;
                        }

                        $metadata->{$handle}->{$key}->value = $this->fieldUidMap[$value->value];
                    }
                }

                if ($property instanceof Field && $metadata->{$handle}) {
                    $metadata->{$handle} = $this->fieldUidMap[$metadata->{$handle}];
                }
            }

            $clone->metadata = json_encode($metadata);
            $clone->save();
        }
    }

    private function updatePermissions(int $id, FormRecord $form): void
    {
        $userId = \Craft::$app->user->getIdentity()->id;
        $permissions = [
            Freeform::PERMISSION_SUBMISSIONS_READ,
            Freeform::PERMISSION_SUBMISSIONS_MANAGE,
            Freeform::PERMISSION_FORMS_MANAGE,
        ];

        foreach ($permissions as $permissionName) {
            $name = strtolower($permissionName.':'.$id);
            $newName = strtolower($permissionName.':'.$form->id);

            $permissionId = (int) (new Query())
                ->select('id')
                ->from(Table::USERPERMISSIONS)
                ->where(['name' => $name])
                ->scalar()
            ;

            $groupIds = (new Query())
                ->select('groupId')
                ->from(Table::USERPERMISSIONS_USERGROUPS)
                ->where(['permissionId' => $permissionId])
                ->column()
            ;

            $userPermissionId = (new Query())
                ->select('id')
                ->from(Table::USERPERMISSIONS_USERS)
                ->where([
                    'permissionId' => $permissionId,
                    'userId' => $userId,
                ])
                ->scalar()
            ;

            $permission = UserPermission::find()->where(['name' => $newName])->one();
            if (!$permission) {
                $permission = new UserPermission();
                $permission->name = $newName;
                $permission->save();
            }

            if ($userPermissionId) {
                $userPermission = new UserPermission_User();
                $userPermission->userId = $userId;
                $userPermission->permissionId = $permission->id;
                $userPermission->save();
            } else {
                foreach ($groupIds as $groupId) {
                    $groupPermission = new UserPermission_UserGroup();
                    $groupPermission->groupId = $groupId;
                    $groupPermission->permissionId = $permission->id;
                    $groupPermission->save();
                }
            }
        }
    }

    private function warmUpLayout(int $formId): void
    {
        $this->layouts = $this->fetchRecords(FormLayoutRecord::class, $formId);
        $this->layoutsUids = $this->fetchRecords(FormLayoutRecord::class, $formId, 'uid');
        $this->pages = $this->fetchRecords(FormPageRecord::class, $formId);
        $this->rows = $this->fetchRecords(FormRowRecord::class, $formId);
        $this->fields = $this->fetchRecords(FormFieldRecord::class, $formId);
        $this->notifications = $this->fetchRecords(FormNotificationRecord::class, $formId);
        $this->integrations = $this->fetchRecords(FormIntegrationRecord::class, $formId);
    }

    private function fetchRecords(string $recordClass, int $formId, string $indexBy = 'id'): array
    {
        // @var ActiveRecord $recordClass
        return $recordClass::find()
            ->where(['formId' => $formId])
            ->indexBy($indexBy)
            ->all()
        ;
    }

    private function buildRuleClone(RuleRecord $original, RuleRecord $clone): void
    {
        $originalBaseRuleRule = $original->getRule()->one();
        if (!$originalBaseRuleRule) {
            return;
        }

        $baseRuleClone = new RuleRecord();
        $baseRuleClone->setAttributes($original->getRule()->one()->getAttributes());
        $baseRuleClone->uid = StringHelper::UUID();
        $baseRuleClone->save();

        $clone->id = $baseRuleClone->id;
        $clone->uid = StringHelper::UUID();
        $clone->save();

        $this->cloneConditions($original->id, $clone->id);
    }

    private function cloneConditions(int $oldId, int $newId): void
    {
        $conditions = RuleConditionRecord::findAll(['ruleId' => $oldId]);
        foreach ($conditions as $condition) {
            $conditionClone = new RuleConditionRecord();
            $conditionClone->ruleId = $newId;
            $conditionClone->fieldId = $this->fields[$condition->fieldId]->id;
            $conditionClone->value = $condition->value;
            $conditionClone->operator = $condition->operator;
            $conditionClone->uid = StringHelper::UUID();
            $conditionClone->save();
        }
    }
}
