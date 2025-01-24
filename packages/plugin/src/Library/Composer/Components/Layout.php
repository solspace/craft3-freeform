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

namespace Solspace\Freeform\Library\Composer\Components;

use Solspace\Freeform\Fields\MailingListField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Fields\FieldCollection;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\DefaultFieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoRenderInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\RecaptchaInterface;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Factories\ComposerFieldFactory;
use Solspace\Freeform\Library\Translations\TranslatorInterface;

class Layout implements \JsonSerializable, \Iterator
{
    private Form $form;

    /** @var Page[] */
    private array $pages = [];

    private FieldCollection $fieldCollection;

    /** @var string[] */
    private array $fieldTypes = [];

    private Properties $properties;

    private array $layoutData;

    private TranslatorInterface $translator;

    public function __construct(
        Form $form,
        array $layoutData,
        Properties $properties,
        TranslatorInterface $translator
    ) {
        $this->form = $form;
        $this->fieldCollection = new FieldCollection();

        $this->properties = $properties;
        $this->layoutData = $layoutData;
        $this->translator = $translator;

        $this->buildLayout();
    }

    public function cloneFieldCollection(): FieldCollection
    {
        $collection = new FieldCollection();
        foreach ($this->getStorableFields() as $field) {
            $collection->add(clone $field);
        }

        return $collection;
    }

    /**
     * @return Page[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    public function getPage(int $index): Page
    {
        if (!isset($this->pages[$index])) {
            throw new FreeformException(
                Freeform::t(
                    "The provided page index '{pageIndex}' does not exist",
                    ['pageIndex' => $index]
                )
            );
        }

        return $this->pages[$index];
    }

    /**
     * @param class-string $implements
     */
    public function hasFields(string $implements): bool
    {
        return \count($this->getFields($implements)) > 0;
    }

    public function getFieldErrorCount(): int
    {
        $fields = $this->getFields();
        $errorCount = 0;
        foreach ($fields as $field) {
            $errorCount += \count($field->getErrors());
        }

        return $errorCount;
    }

    public function getFields(string $implements = null): array
    {
        return $this->fieldCollection->getList($implements);
    }

    public function getStorableFields(string $implements = null): array
    {
        return array_filter(
            $this->getFields($implements),
            fn ($field) => !$field instanceof NoStorageInterface && $field->getHandle()
        );
    }

    public function getHiddenFields(): array
    {
        return array_filter(
            $this->getFields(),
            fn ($field) => $field instanceof NoRenderInterface || ($field instanceof MailingListField && $field->isHidden())
        );
    }

    public function hasFieldType(string $type): bool
    {
        return \in_array(strtolower($type), $this->fieldTypes, true);
    }

    public function getFieldsByHandle(): array
    {
        return $this->fieldCollection->getIndexedByHandle();
    }

    public function getFieldById(int $id): FieldInterface
    {
        return $this->fieldCollection->get($id);
    }

    public function getFieldByHandle(string $handle): FieldInterface
    {
        return $this->fieldCollection->get($handle);
    }

    public function getFieldByHash(string $hash): FieldInterface
    {
        return $this->fieldCollection->get($hash);
    }

    public function getSpecialField(string $name): AbstractField
    {
        $name = strtolower($name);
        if ('recaptcha' === $name && $this->hasFields(RecaptchaInterface::class)) {
            $fields = $this->getFields(RecaptchaInterface::class);

            return reset($fields);
        }

        throw new FreeformException(
            $this->translate("Special Field with name '{name}' not found", ['name' => $name])
        );
    }

    /**
     * Removes a given field form the layoutData if it's present
     * ** DOES NOT REMOVE IT FROM LAYOUT OBJECT **
     * !!This is meant only for cleaning up the export JSON data!!
     */
    public function removeFieldFromData(AbstractField $field)
    {
        foreach ($this->layoutData as $pageIndex => $page) {
            foreach ($page as $rowIndex => $row) {
                foreach ($row['columns'] as $columnIndex => $column) {
                    if ($column === $field->getHash()) {
                        unset($this->layoutData[$pageIndex][$rowIndex]['columns'][$columnIndex]);
                        $this->layoutData[$pageIndex][$rowIndex]['columns'] = array_values(
                            $this->layoutData[$pageIndex][$rowIndex]['columns']
                        );

                        break;
                    }
                }
            }
        }

        foreach ($this->layoutData as $pageIndex => $page) {
            foreach ($page as $rowIndex => $row) {
                if (0 === \count($row['columns'])) {
                    unset($this->layoutData[$pageIndex][$rowIndex]);
                    $this->layoutData[$pageIndex] = array_values($this->layoutData[$pageIndex]);
                }
            }
        }
    }

    public function jsonSerialize(): array
    {
        return $this->layoutData;
    }

    public function current(): false|Page
    {
        return current($this->pages);
    }

    public function next(): void
    {
        next($this->pages);
    }

    public function key(): ?int
    {
        return key($this->pages);
    }

    public function valid(): bool
    {
        return null !== $this->key() && false !== $this->key();
    }

    public function rewind(): void
    {
        reset($this->pages);
    }

    /**
     * Builds all page, row and field objects and hydrates them.
     *
     * @throws ComposerException
     */
    private function buildLayout()
    {
        $availableFieldTypes = array_keys(Freeform::getInstance()->fields->getFieldTypes());

        $pageObjects = [];
        $allFields = [];

        foreach ($this->layoutData as $pageIndex => $rows) {
            if (!\is_array($rows)) {
                throw new ComposerException(
                    $this->translate(
                        'Layout page {pageIndex} does not contain a row array',
                        ['pageIndex' => $pageIndex]
                    )
                );
            }

            $rowObjects = $pageFields = [];
            foreach ($rows as $rowIndex => $rowData) {
                if (!isset($rowData['id'])) {
                    throw new ComposerException(
                        $this->translate(
                            'Layout page {pageIndex} row {rowIndex} does not contain its ID',
                            ['pageIndex' => $pageIndex, 'rowIndex' => $rowIndex]
                        )
                    );
                }

                if (!isset($rowData['columns']) || !\is_array($rowData['columns'])) {
                    throw new ComposerException(
                        $this->translate(
                            'Layout page {pageIndex} row {rowIndex} does not contain a list of columns',
                            ['pageIndex' => $pageIndex, 'rowIndex' => $rowIndex]
                        )
                    );
                }

                $columns = $rowData['columns'];

                $fields = [];
                foreach ($columns as $fieldHash) {
                    $fieldProperties = $this->properties->getFieldProperties($fieldHash);

                    try {
                        $field = ComposerFieldFactory::createFromProperties(
                            $this->form,
                            $fieldProperties,
                            $pageIndex
                        );
                    } catch (\Exception) {
                        continue;
                    }

                    if (!\in_array($field->getType(), $availableFieldTypes, true)) {
                        if (!$field instanceof DefaultFieldInterface) {
                            continue;
                        }
                    }

                    $this->fieldCollection->add($field);

                    $pageFields[] = $field;

                    if ($field instanceof NoRenderInterface || ($field instanceof MailingListField && $field->isHidden())) {
                        continue;
                    }

                    $fields[] = $field;

                    $this->fieldTypes[] = $field->getType();
                }

                if (empty($fields)) {
                    continue;
                }

                $rowId = $rowData['id'];
                $row = new Row($rowId, $fields);

                $rowObjects[] = $row;
            }

            $pageProperties = $this->properties->getPageProperties($pageIndex);
            $page = new Page($pageIndex, $pageProperties->getLabel(), $rowObjects, $pageFields);

            $pageObjects[] = $page;
        }

        $this->fieldTypes = array_unique($this->fieldTypes);
        $this->fieldTypes = array_filter($this->fieldTypes);

        $this->pages = $pageObjects;
    }

    private function translate(string $string, array $variables = []): string
    {
        return $this->translator->translate($string, $variables);
    }
}
