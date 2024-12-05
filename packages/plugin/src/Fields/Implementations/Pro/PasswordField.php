<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Interfaces\CharacterVariabilityInterface;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\MinLengthInterface;
use Solspace\Freeform\Fields\Interfaces\NoEmailPresenceInterface;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Fields\Interfaces\RememberPostedValueInterface;
use Solspace\Freeform\Fields\Traits\CharacterVariabilityTrait;
use Solspace\Freeform\Fields\Traits\MinLengthTrait;

#[Type(
    name: 'Password',
    typeShorthand: 'password',
    iconPath: __DIR__.'/../Icons/password.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/text.ejs',
)]
class PasswordField extends TextField implements NoStorageInterface, ExtraFieldInterface, RememberPostedValueInterface, NoEmailPresenceInterface, MinLengthInterface, CharacterVariabilityInterface
{
    use CharacterVariabilityTrait;
    use MinLengthTrait;

    #[Input\Hidden]
    protected bool $encrypted = false;

    public function getType(): string
    {
        return self::TYPE_PASSWORD;
    }

    public function getInputHtml(): string
    {
        $output = parent::getInputHtml();

        return str_replace('type="text"', 'type="password"', $output);
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();

        if (!empty($this->getMinLength())) {
            $description[] = 'Min length: '.$this->getMinLength().'.';
        }

        if (!empty($this->getMaxLength())) {
            $description[] = 'Max length: '.$this->getMaxLength().'.';
        }

        if ($this->isUseCharacterVariability()) {
            $description[] = 'Character Variability: The value should contain at least one number, one lowercase letter, one uppercase letter, and one special character.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }
}
