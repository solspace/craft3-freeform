<?php

namespace Solspace\Freeform\Bundles\Notifications\Providers;

use Solspace\Freeform\Attributes\Notification\Type;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Events\Notifications\RegisterNotificationTypesEvent;
use yii\base\Event;

class NotificationTypesProvider
{
    public const EVENT_REGISTER_NOTIFICATION_TYPES = 'registerNotificationTypes';

    public function __construct(private PropertyProvider $propertyProvider) {}

    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        $event = new RegisterNotificationTypesEvent($this->propertyProvider);

        Event::trigger(self::class, self::EVENT_REGISTER_NOTIFICATION_TYPES, $event);

        return $event->getTypes();
    }

    public function getTypeDefinition(string $class): ?Type
    {
        $types = $this->getTypes();

        foreach ($types as $type) {
            if ($type->className === $class) {
                return $type;
            }
        }

        return null;
    }
}
