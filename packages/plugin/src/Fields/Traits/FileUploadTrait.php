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

namespace Solspace\Freeform\Fields\Traits;

use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\ValueGenerator;
use Solspace\Freeform\Fields\Implementations\Options\AssetSourceOptions;
use Solspace\Freeform\Fields\Implementations\ValueGenerators\AssetSourceGenerator;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;

trait FileUploadTrait
{
    #[Flag(FileUploadInterface::FLAG_GLOBAL_PROPERTY)]
    #[ValueGenerator(AssetSourceGenerator::class)]
    #[Input\Select(
        label: 'Asset Source',
        instructions: 'Select an asset source to be able to store user uploaded files.',
        order: 1,
        options: AssetSourceOptions::class,
    )]
    protected ?int $assetSourceId = null;

    #[Flag(FileUploadInterface::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Upload Location',
        instructions: 'The subfolder path that files should be uploaded to. May contain {{ form.handle }} or {{ form.id }} variables as well.',
        order: 2,
        placeholder: 'path/to/subfolder',
    )]
    protected ?string $defaultUploadLocation = null;

    public function getAssetSourceId(): ?int
    {
        return $this->assetSourceId;
    }

    public function getDefaultUploadLocation(): ?string
    {
        return $this->defaultUploadLocation;
    }
}
