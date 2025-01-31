<?php

namespace Solspace\Freeform\Bundles\GraphQL;

use craft\events\RegisterGqlMutationsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\services\Gql;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\AttributeInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\AttributesInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\ButtonsAttributesInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\ButtonsInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\DynamicNotificationInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\FieldInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\FieldRuleInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\FormInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\FormPropertiesInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\FreeformInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\GoogleTagManagerInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\NotificationTemplateInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\OpinionScaleInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\OptionInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\PageInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\PageRuleInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\PostForwardingInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\RowInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\RuleConditionInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\RulesInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\CsrfTokenInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\FormCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\HoneypotInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\SubmissionCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SubmissionInterface;
use Solspace\Freeform\Bundles\GraphQL\Mutations\SubmissionMutation;
use Solspace\Freeform\Bundles\GraphQL\Queries\FreeformQuery;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class GraphQLBundle extends FeatureBundle
{
    public function __construct()
    {
        if (version_compare(\Craft::$app->version, '3.5.0', '<')) {
            return;
        }

        if ($this->plugin()->edition()->isBelow(Freeform::EDITION_LITE)) {
            return;
        }

        $freeform = Freeform::getInstance();
        if ($freeform->settings->getSettingsModel()->allowDashesInFieldHandles) {
            return;
        }

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function (RegisterGqlTypesEvent $event) {
                $event->types[] = FreeformInterface::class;
                $event->types[] = FormInterface::class;
                $event->types[] = FieldInterface::class;
                $event->types[] = PageInterface::class;
                $event->types[] = RowInterface::class;
                $event->types[] = OptionInterface::class;
                $event->types[] = OpinionScaleInterface::class;
                $event->types[] = HoneypotInterface::class;
                $event->types[] = CsrfTokenInterface::class;
                $event->types[] = FormCaptchaInterface::class;
                $event->types[] = SubmissionCaptchaInterface::class;
                $event->types[] = AttributeInterface::class;
                $event->types[] = AttributesInterface::class;
                $event->types[] = SubmissionInterface::class;
                $event->types[] = ButtonsInterface::class;
                $event->types[] = ButtonsAttributesInterface::class;
                $event->types[] = NotificationTemplateInterface::class;
                $event->types[] = RuleConditionInterface::class;
                $event->types[] = PageRuleInterface::class;
                $event->types[] = FieldRuleInterface::class;
                $event->types[] = RulesInterface::class;
                $event->types[] = FormPropertiesInterface::class;
                $event->types[] = DynamicNotificationInterface::class;
                $event->types[] = PostForwardingInterface::class;
                $event->types[] = GoogleTagManagerInterface::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function (RegisterGqlQueriesEvent $event) {
                $event->queries = array_merge(
                    $event->queries,
                    FreeformQuery::getQueries()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_MUTATIONS,
            function (RegisterGqlMutationsEvent $event) {
                $event->mutations = array_merge(
                    $event->mutations,
                    SubmissionMutation::getMutations()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
            function (RegisterGqlSchemaComponentsEvent $event) use ($freeform) {
                $group = $freeform->name;
                $forms = $freeform->forms->getAllForms();

                $formsCategory = GqlPermissions::CATEGORY_FORMS;
                $submissionsCategory = GqlPermissions::CATEGORY_SUBMISSIONS;

                $queries = [];
                $queries[$formsCategory.'.all:read'] = [
                    'label' => Freeform::t('View all forms'),
                ];

                $mutations = [];
                $mutations[$submissionsCategory.'.all:create'] = [
                    'label' => Freeform::t('Create submissions for all forms'),
                ];

                foreach ($forms as $form) {
                    $formUid = $form->getUid();
                    $formName = $form->getName();

                    $formsScopeByContext = $formsCategory.'.'.$formUid;

                    $queries[$formsScopeByContext.':read'] = [
                        'label' => Freeform::t('View "{formName}" form', [
                            'formName' => $formName,
                        ]),
                    ];

                    $submissionsScopeByContext = $submissionsCategory.'.'.$formUid;

                    $mutations[$submissionsScopeByContext.':create'] = [
                        'label' => Freeform::t('Create submissions for form "{formName}"', [
                            'formName' => $formName,
                        ]),
                    ];
                }

                $event->queries[$group] = $queries;

                $event->mutations[$group] = $mutations;
            }
        );

        Event::on(
            FormsController::class,
            FormsController::EVENT_CREATE_FORM,
            function (PersistFormEvent $event) {
                if (!$event->hasErrors()) {
                    $this->flushCaches();
                }
            }
        );

        Event::on(
            FormsController::class,
            FormsController::EVENT_UPSERT_FORM,
            function (PersistFormEvent $event) {
                if (!$event->hasErrors()) {
                    $this->flushCaches();
                }
            }
        );
    }

    private function flushCaches(): void
    {
        \Craft::$app->gql->flushCaches();
    }
}
