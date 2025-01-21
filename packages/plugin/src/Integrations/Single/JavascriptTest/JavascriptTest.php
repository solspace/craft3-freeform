<?php

namespace Solspace\Freeform\Integrations\Single\JavascriptTest;

use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Input\Text;
use Solspace\Freeform\Attributes\Property\Input\TextArea;
use Solspace\Freeform\Attributes\Property\Middleware;
use Solspace\Freeform\Library\Integrations\BaseIntegration;
use Solspace\Freeform\Library\Integrations\EnabledByDefault\EnabledByDefaultTrait;
use Solspace\Freeform\Library\Integrations\SingletonIntegrationInterface;

/**
 * @internal
 *
 * @coversNothing
 */
#[Type(
    name: 'Javascript Test',
    type: Type::TYPE_SINGLE,
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class JavascriptTest extends BaseIntegration implements SingletonIntegrationInterface
{
    use EnabledByDefaultTrait;

    private const DEFAULT_INPUT_NAME = 'freeform_check';
    private const DEFAULT_MESSAGE = 'Javascript must be enabled to submit this form';

    #[Middleware('handle')]
    #[Text(
        label: 'Custom Input Name',
        instructions: "Enter a new value to rename the default Freeform Javascript Test input name. The default is 'freeform_check'.",
        placeholder: 'e.g. freeform_check',
    )]
    protected string $inputName = '';

    #[TextArea(
        label: 'Custom Error Message',
        instructions: 'Enter a new value to change the default error message for the Freeform Javascript Test. This is only applied if the Spam Behavior setting is set to Display Error Messages.',
        placeholder: self::DEFAULT_MESSAGE,
    )]
    protected string $errorMessage = '';

    public function getInputName(): string
    {
        return $this->getProcessedValue($this->inputName) ?: self::DEFAULT_INPUT_NAME;
    }

    public function getErrorMessage(): string
    {
        return $this->getProcessedValue($this->errorMessage) ?: self::DEFAULT_MESSAGE;
    }
}
