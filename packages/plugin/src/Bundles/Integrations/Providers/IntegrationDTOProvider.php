<?php

namespace Solspace\Freeform\Bundles\Integrations\Providers;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Library\DataObjects\Integrations\Integration;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Models\IntegrationModel;

class IntegrationDTOProvider
{
    public function __construct(
        private PropertyProvider $propertyProvider,
        private IntegrationTypeProvider $typeProvider,
    ) {}

    public function convertOne(IntegrationModel $model): ?Integration
    {
        return $this->createDtoFromModel($model);
    }

    /**
     * @param IntegrationInterface[] $integrations
     *
     * @return Integration[]
     */
    public function convert(array $integrations): array
    {
        return array_values(
            array_filter(
                array_map(
                    fn ($model) => $this->createDTOFromModel($model),
                    $integrations
                )
            )
        );
    }

    private function createDTOFromModel(IntegrationInterface $integration): ?Integration
    {
        $type = $integration->getTypeDefinition();

        $icon = $type->iconPath;
        if ($icon) {
            [$_, $icon] = \Craft::$app->assetManager->publish($icon);
        }

        $dto = new Integration();
        $dto->id = $integration->getId();
        $dto->uid = $integration->getUid();
        $dto->name = $integration->getName();
        $dto->handle = $integration->getHandle();
        $dto->enabled = (bool) $integration->isEnabled();
        $dto->type = $type->type;
        $dto->shortName = $type->shortName;
        $dto->icon = $icon;
        $dto->properties = $this->propertyProvider->getEditableProperties($integration);
        $dto->properties->removeFlagged(
            IntegrationInterface::FLAG_INTERNAL,
            IntegrationInterface::FLAG_GLOBAL_PROPERTY,
        );

        foreach ($dto->properties as $property) {
            if ($property->hasFlag(IntegrationInterface::FLAG_AS_HIDDEN_IN_INSTANCE)) {
                $property->type = 'hidden';
            }
        }

        return $dto;
    }
}
