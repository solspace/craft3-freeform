<?php

namespace Solspace\Freeform\Bundles\Form\Types\Regular;

use craft\helpers\UrlHelper;
use Solspace\Freeform\Bundles\Transformers\Builder\Form\FormTransformer;
use Solspace\Freeform\Events\Forms\GenerateLinksEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use yii\base\Event;

class AttachFormLinks extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            FormTransformer::class,
            FormTransformer::EVENT_ATTACH_LINKS,
            function (GenerateLinksEvent $event) {
                $form = $event->getForm();
                $data = $event->getFormData();

                static $isSpamEnabled;
                if (null === $isSpamEnabled) {
                    $isSpamEnabled = Freeform::getInstance()->settings->isSpamFolderEnabled();
                }

                $canManageForm = PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_MANAGE);
                if (!$canManageForm) {
                    $canManageForm = PermissionHelper::checkPermission(
                        PermissionHelper::prepareNestedPermission(
                            Freeform::PERMISSION_FORMS_MANAGE,
                            $form->getId()
                        )
                    );
                }

                $canReadSubmissions = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_READ);
                if (!$canReadSubmissions) {
                    $canReadSubmissions = PermissionHelper::checkPermission(
                        PermissionHelper::prepareNestedPermission(
                            Freeform::PERMISSION_SUBMISSIONS_READ,
                            $form->getId()
                        )
                    );
                }

                $canManageSubmissions = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
                if (!$canManageSubmissions) {
                    $canManageSubmissions = PermissionHelper::checkPermission(
                        PermissionHelper::prepareNestedPermission(
                            Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                            $form->getId()
                        )
                    );
                }

                $submissionCount = $data->counters['submissions'];
                $spamCount = $data->counters['spam'];
                $savedCount = $data->counters['saved'];

                $submissions = Freeform::t('{count} Submissions', ['count' => $submissionCount]);
                $spam = Freeform::t('{count} Spam', ['count' => $spamCount]);
                $saved = Freeform::t('{count} Saved', ['count' => $savedCount]);

                if ($canManageForm) {
                    $event->add(
                        $form,
                        'formTitle',
                        UrlHelper::cpUrl('freeform/forms/'.$form->getId()),
                        'title'
                    );
                }

                $event->add(
                    $submissions,
                    'submissions',
                    $canManageSubmissions ? UrlHelper::cpUrl('freeform/submissions?source=form:'.$form->getId()) : null,
                    'linkList',
                    $submissionCount,
                );

                $event->add(
                    $spam,
                    'spam',
                    $isSpamEnabled && $canManageSubmissions ? UrlHelper::cpUrl('freeform/spam?source=form:'.$form->getId()) : null,
                    'linkList',
                    $spamCount,
                );

                if ($savedCount) {
                    $event->add(
                        $saved,
                        'saved',
                        null,
                        'linkList',
                        $savedCount,
                    );
                }
            }
        );
    }
}
