<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Implementations\OpinionScale\LegendsTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\OpinionScale\ScalesTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Fields\BaseOptionsField;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\OptionsInterface;
use Solspace\Freeform\Fields\Properties\OpinionScale\Legend;
use Solspace\Freeform\Fields\Properties\OpinionScale\Scale;

#[Type(
    name: 'Opinion Scale',
    typeShorthand: 'opinion-scale',
    iconPath: __DIR__.'/../Icons/opinion-scale.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/opinion-scale.ejs',
)]
class OpinionScaleField extends BaseOptionsField implements ExtraFieldInterface, OptionsInterface
{
    #[ValueTransformer(ScalesTransformer::class)]
    #[Input\TabularData(
        label: 'Scales',
        instructions: 'The options a user can choose from.',
        value: [],
        configuration: [
            [
                'key' => 'value',
                'label' => 'Value',
            ],
            [
                'key' => 'label',
                'label' => 'Label (Optional)',
            ],
        ],
    )]
    protected array $scales = [];

    #[ValueTransformer(LegendsTransformer::class)]
    #[Input\TabularData(
        label: 'Legends',
        instructions: 'Descriptions of options or ranges of options (does not need to match the number of options available).',
        value: [],
        configuration: [
            ['key' => 'label', 'label' => 'Legend'],
        ],
    )]
    protected array $legends = [];

    public function getType(): string
    {
        return self::TYPE_OPINION_SCALE;
    }

    /**
     * @return Scale[]
     */
    public function getScales(): array
    {
        return $this->scales;
    }

    /**
     * @return Legend[]
     */
    public function getLegends(): array
    {
        return $this->legends;
    }

    public function getOptions(): OptionCollection
    {
        $collection = new OptionCollection();
        foreach ($this->getScales() as $scale) {
            $value = $scale->getValue();
            $label = $scale->getLabel();

            $collection->add($value, $label);
        }

        return $collection;
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Single option value allowed.';

        $values = [];
        foreach ($this->getScales() as $scale) {
            $values[] = '"'.$scale->getValue().'"';
        }

        if (!empty($values)) {
            $description[] = 'Options include '.implode(', ', $values).'.';
        }

        $legends = [];
        foreach ($this->getLegends() as $legend) {
            $legends[] = '"'.$legend.'"';
        }

        if (!empty($legends)) {
            $description[] = 'Legends include '.implode(' to ', $legends).'.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    protected function getInputHtml(): string
    {
        if (empty($this->scales)) {
            return '';
        }

        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', 'radio')
        ;

        $output = '<div class="opinion-scale">';

        $output .= '<ul class="opinion-scale-scales">';
        foreach ($this->getScales() as $index => $scale) {
            $label = $scale->getLabel();
            $value = $scale->getValue();

            $label = $label ?: $value;

            $isSelected = $value == $this->getValue();
            $id = $this->getIdAttribute()."-{$index}";

            $inputAttributes = $attributes
                ->clone()
                ->replace('id', $id)
                ->replace('value', $value)
                ->replace('checked', $isSelected)
            ;

            $output .= '<li>';

            $output .= '<input'.$inputAttributes.' />';

            $output .= '<label for="'.$id.'">';
            $output .= $this->translateOption('scales', $value, $label);
            $output .= '</label>';

            $output .= '</li>';
        }
        $output .= '</ul>';

        if ($this->getLegends()) {
            $output .= '<ul class="opinion-scale-legends">';
            foreach ($this->getLegends() as $legend) {
                $output .= '<li>';
                $output .= (string) $legend;
                $output .= '</li>';
            }
            $output .= '</ul>';
        }

        $output .= '</div>';

        return $output;
    }
}
