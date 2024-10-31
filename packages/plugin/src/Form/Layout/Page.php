<?php

namespace Solspace\Freeform\Form\Layout;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Form\Layout\Page\Buttons\PageButtons;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Collections\FieldCollection;
use Solspace\Freeform\Library\Collections\RowCollection;
use Solspace\Freeform\Services\Form\TranslationsService;

/**
 * @implements \IteratorAggregate<int, Row>
 */
class Page implements \IteratorAggregate
{
    private ?int $id;
    private ?string $uid;
    private string $label;
    private int $index;

    private PageButtons $buttons;

    public function __construct(
        private Form $form,
        private PropertyProvider $propertyProvider,
        private Layout $layout,
        array $config = [],
    ) {
        $this->id = $config['id'] ?? null;
        $this->uid = $config['uid'] ?? null;
        $this->label = $config['label'] ?? '';
        $this->index = $config['index'] ?? 0;

        $buttons = new PageButtons($this, []);
        $this->propertyProvider->setObjectProperties($buttons, $config['metadata']['buttons'] ?? []);

        $this->buttons = $buttons;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function getLabel(): string
    {
        return Freeform::getInstance()->translations
            ->getTranslation(
                $this->getForm(),
                TranslationsService::TYPE_PAGES,
                $this->getUid(),
                'label',
                $this->label
            )
        ;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getLayout(): Layout
    {
        return $this->layout;
    }

    public function getRows(): RowCollection
    {
        return $this->layout->getRows();
    }

    public function getFields(null|array|string $implements = null, ?string $strategy = null): FieldCollection
    {
        return $this->layout->getFields()->getList($implements, $strategy);
    }

    public function getButtons(): PageButtons
    {
        return $this->buttons;
    }

    public function isFirst(): bool
    {
        return 0 === $this->index;
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->layout->getRows()->getIterator();
    }
}
