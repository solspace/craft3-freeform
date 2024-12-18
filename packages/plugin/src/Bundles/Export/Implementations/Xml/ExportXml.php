<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Xml;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;

class ExportXml extends AbstractSubmissionExport
{
    public static function getLabel(): string
    {
        return 'XML';
    }

    public function getMimeType(): string
    {
        return 'text/xml';
    }

    public function getFileExtension(): string
    {
        return 'xml';
    }

    public function export(): string
    {
        $xml = new \SimpleXMLElement('<root/>');

        foreach ($this->getRowBatch() as $row) {
            $submission = $xml->addChild('submission');

            foreach ($row as $column) {
                $field = $column->getField();
                $value = $column->getValue();

                if ($field && $field instanceof MultiValueInterface) {
                    $node = $submission->addChild($column->getHandle());

                    if ($field instanceof TableField) {
                        $layout = $field->getTableLayout();
                        $value = \is_array($value) ? $value : [];
                        foreach ($value as $tableRow) {
                            $rowNode = $node->addChild('row');

                            foreach ($tableRow as $index => $columnValue) {
                                $columnNode = $rowNode->addChild('column', htmlspecialchars($columnValue));

                                $label = $layout[$index]->label ?? null;
                                if ($label) {
                                    $columnNode->addAttribute('label', $label);
                                }
                            }
                        }
                    } elseif (\is_array($value)) {
                        foreach ($value as $item) {
                            $node->addChild('item', htmlspecialchars($item));
                        }
                    }
                } else {
                    $node = $submission->addChild(
                        $column->getHandle(),
                        htmlspecialchars($column->getValue())
                    );
                }

                $node->addAttribute('label', $column->getLabel());
            }
        }

        return $this->formatXml($xml);
    }

    protected function formatXml(\SimpleXMLElement $element): string
    {
        $xmlDocument = new \DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($element->asXML());

        return $xmlDocument->saveXML();
    }
}
