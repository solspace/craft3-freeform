<?php

namespace Solspace\Freeform\Bundles\Permissions;

use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\LimitedUsersRecord;
use yii\base\Event;

class PermissionsBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            [$this, 'loadPermissions'],
        );
    }

    public function loadPermissions(RegisterUserPermissionsEvent $event): void
    {
        if (\Craft::Solo === \Craft::$app->getEdition()) {
            return;
        }

        $forms = $this->plugin()->forms->getAllFormNames();

        $readPermissions = $managePermissions = $formPermissions = [];
        foreach ($forms as $id => $name) {
            $readKey = PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_READ,
                $id
            );
            $manageKey = PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                $id
            );
            $formPermissionName = PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_FORMS_MANAGE,
                $id
            );

            $readPermissions[$readKey] = ['label' => $name];
            $managePermissions[$manageKey] = ['label' => $name];
            $formPermissions[$formPermissionName] = ['label' => $name];
        }

        $permissions = array_merge(
            $this->getSubmissionPermissions($readPermissions, $managePermissions),
            $this->getFormPermissions($formPermissions),
            $this->getNotificationPermissions(),
            $this->getPdfTemplatePermissions(),
            $this->getExportPermissions(),
            $this->getSettingsPermissions(),
            $this->getLimitedUsersPermissions(),
        );

        $event->permissions[] = [
            'heading' => $this->plugin()->name,
            'permissions' => $permissions,
        ];
    }

    private function getSubmissionPermissions(array $readPermissions, array $managePermissions): array
    {
        return [
            Freeform::PERMISSION_SUBMISSIONS_ACCESS => [
                'label' => Freeform::t('Access Submissions'),
                'nested' => [
                    Freeform::PERMISSION_SUBMISSIONS_READ => [
                        'label' => Freeform::t('Read All Submissions'),
                        'info' => Freeform::t("If you'd like to give users access to read all forms' submissions, check off this checkbox. It will also override any selections in the 'Read Submissions by Form' settings. 'Manage' permissions will also override any 'Read' permissions."),
                    ],
                    Freeform::PERMISSION_SUBMISSIONS_READ_INDIVIDUAL => [
                        'label' => Freeform::t('Read Submissions by Form'),
                        'info' => Freeform::t("If you'd like to give users access to read only some forms' submissions, check off the ones here. These selections will be overridden by the 'Read All Submissions' checkbox. 'Manage' permissions will also override any 'Read' permissions."),
                        'nested' => $readPermissions,
                    ],
                    Freeform::PERMISSION_SUBMISSIONS_MANAGE => [
                        'label' => Freeform::t('Manage All Submissions'),
                        'info' => Freeform::t("If you'd like to give users access to manage all forms' submissions, check off this checkbox. It will also override any selections in the 'Manage Submissions by Form' settings. 'Manage' permissions will also override any 'Read' permissions."),
                    ],
                    Freeform::PERMISSION_SUBMISSIONS_MANAGE_INDIVIDUAL => [
                        'label' => Freeform::t('Manage Submissions by Form'),
                        'info' => Freeform::t("If you'd like to give users access to manage only some forms' submissions, check off the ones here. These selections will be overridden by the 'Manage All Submissions' checkbox. 'Manage' permissions will also override any 'Read' permissions."),
                        'nested' => $managePermissions,
                    ],
                ],
            ],
        ];
    }

    private function getFormPermissions(array $formPermissions): array
    {
        return [
            Freeform::PERMISSION_FORMS_ACCESS => [
                'label' => Freeform::t('Access Forms'),
                'nested' => [
                    Freeform::PERMISSION_FORMS_CREATE => ['label' => Freeform::t('Create New Forms')],
                    Freeform::PERMISSION_FORMS_DELETE => ['label' => Freeform::t('Delete Forms')],
                    Freeform::PERMISSION_FORMS_MANAGE => [
                        'label' => Freeform::t('Manage All Forms'),
                        'info' => Freeform::t("If you'd like to give users access to all forms, check off this checkbox. It will also override any selections in the 'Manage Forms Individually' settings."),
                    ],
                    Freeform::PERMISSION_FORMS_MANAGE_INDIVIDUAL => [
                        'label' => Freeform::t('Manage Forms Individually'),
                        'info' => Freeform::t("If you'd like to give users access to only some forms, check off the ones here. These selections will be overridden by the 'Manage All Forms' checkbox."),
                        'nested' => $formPermissions,
                    ],
                ],
            ],
        ];
    }

    private function getNotificationPermissions(): array
    {
        return [
            Freeform::PERMISSION_NOTIFICATIONS_ACCESS => [
                'label' => Freeform::t('Access Email Templates'),
                'nested' => [
                    Freeform::PERMISSION_NOTIFICATIONS_MANAGE => [
                        'label' => Freeform::t(
                            'Manage Email Templates'
                        ),
                    ],
                ],
            ],
        ];
    }

    private function getPdfTemplatePermissions(): array
    {
        return [
            Freeform::PERMISSION_PDF_TEMPLATES_ACCESS => [
                'label' => Freeform::t('Access PDF Templates'),
                'nested' => [
                    Freeform::PERMISSION_PDF_TEMPLATES_MANAGE => [
                        'label' => Freeform::t(
                            'Manage PDF Templates'
                        ),
                    ],
                ],
            ],
        ];
    }

    private function getExportPermissions(): array
    {
        return [
            Freeform::PERMISSION_ACCESS_QUICK_EXPORT => ['label' => Freeform::t('Access Quick Exporting')],
            Freeform::PERMISSION_EXPORT_PROFILES_ACCESS => [
                'label' => Freeform::t('Access Export Profiles'),
                'nested' => [
                    Freeform::PERMISSION_EXPORT_PROFILES_MANAGE => [
                        'label' => Freeform::t(
                            'Manage Export Profiles'
                        ),
                    ],
                ],
            ],
            Freeform::PERMISSION_EXPORT_NOTIFICATIONS_ACCESS => [
                'label' => Freeform::t('Access Export Notifications'),
                'nested' => [
                    Freeform::PERMISSION_EXPORT_NOTIFICATIONS_MANAGE => [
                        'label' => Freeform::t(
                            'Manage Export Notifications'
                        ),
                    ],
                ],
            ],
        ];
    }

    private function getSettingsPermissions(): array
    {
        return [
            Freeform::PERMISSION_SETTINGS_ACCESS => ['label' => Freeform::t('Access Settings')],
        ];
    }

    private function getLimitedUsersPermissions(): array
    {
        $records = LimitedUsersRecord::find()->all();
        if (empty($records)) {
            return [];
        }

        $permissions = [];
        foreach ($records as $record) {
            $handle = PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_LIMITED_USERS,
                $record->id
            );
            $permissions[$handle] = ['label' => $record->name];
        }

        return [
            Freeform::PERMISSION_LIMITED_USERS => [
                'label' => Freeform::t('Limited Users'),
                'info' => Freeform::t('Enable limited users functionality.'),
                'nested' => $permissions,
            ],
        ];
    }
}
