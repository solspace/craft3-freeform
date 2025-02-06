<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Bundles\Notifications\Providers;

use Solspace\Freeform\Attributes\Notification\Type;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Notifications\NotificationInterface;
use Solspace\Freeform\Records\Form\FormNotificationRecord;

/**
 * @template T of NotificationInterface
 */
class NotificationsProvider
{
    public function __construct(private PropertyProvider $propertyProvider) {}

    public function getById(int $id): ?NotificationInterface
    {
        $record = FormNotificationRecord::findOne(['id' => $id]);
        if (!$record) {
            return null;
        }

        return $this->createNotificationObjects($record);
    }

    /**
     * @return FormNotificationRecord[]
     */
    public function getRecordsByForm(?Form $form = null): array
    {
        return FormNotificationRecord::find()
            ->where(['formId' => $form?->getId()])
            ->all()
        ;
    }

    public function getByFormAndId(Form $form, int $id): ?NotificationInterface
    {
        $record = FormNotificationRecord::findOne([
            'formId' => $form->getId(),
            'id' => $id,
        ]);

        return $record ? $this->createNotificationObjects($record) : null;
    }

    public function getByForm(?Form $form = null): array
    {
        $records = $this->getRecordsByForm($form);

        $notifications = [];
        foreach ($records as $record) {
            $notifications[] = $this->createNotificationObjects($record);
        }

        return array_filter($notifications);
    }

    /**
     * @param class-string<T> $class
     *
     * @return T[]
     */
    public function getByFormAndClass(Form $form, string $class): array
    {
        $records = FormNotificationRecord::find()
            ->where([
                'formId' => $form->getId(),
                'class' => $class,
                'enabled' => true,
            ])
            ->all()
        ;

        $notifications = [];
        foreach ($records as $record) {
            $notifications[] = $this->createNotificationObjects($record);
        }

        return array_filter($notifications);
    }

    private function createNotificationObjects(FormNotificationRecord $record): ?NotificationInterface
    {
        /** @var NotificationInterface $class */
        $class = $record->class;

        $reflection = new \ReflectionClass($class);
        if (!$reflection->implementsInterface(NotificationInterface::class)) {
            return null;
        }

        $typeAttributes = $reflection->getAttributes(Type::class);
        $type = reset($typeAttributes);

        $type = $type ? $type->newInstance() : null;

        /** @var Type $type */
        if (!$type) {
            return null;
        }

        $metadata = JsonHelper::decode($record->metadata, true);

        $metadata['id'] = $record->id;
        $metadata['uid'] = $record->uid;
        $metadata['enabled'] = $record->enabled;

        $notification = new $class();
        $this->propertyProvider->setObjectProperties($notification, $metadata);

        return $notification;
    }
}
