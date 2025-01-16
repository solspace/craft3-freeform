<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Xml;

use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
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

    public function export($resource): void
    {
        $xml = new XMLWriter();

        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('root');

        foreach ($this->getRowBatch() as $rows) {
            foreach ($rows as $columns) {
                $xml->startElement('submission');

                foreach ($columns as $column) {
                    $field = $column->getField();
                    $value = $column->getValue();
                    $handle = $column->getDescriptor()->getId();
                    $label = $column->getDescriptor()->getLabel();

                    if ($field) {
                        $handle = $field->getHandle();
                    }

                    $xml->startElement($handle);

                    if ($field instanceof MultiValueInterface) {
                        if ($field instanceof TableField) {
                            $xml->writeAttribute('label', $label);

                            $layout = $field->getTableLayout();
                            $value = \is_array($value) ? $value : [];

                            foreach ($value as $tableRow) {
                                $xml->startElement('row');

                                foreach ($tableRow as $index => $columnValue) {
                                    $xml->startElement('column');

                                    $label = $layout[$index]->label ?? null;
                                    if ($label) {
                                        $xml->writeAttribute('label', $layout[$index]->label);
                                    }

                                    if ($columnValue) {
                                        $xml->text(htmlspecialchars($columnValue));
                                    }

                                    $xml->endElement(); // column
                                }

                                $xml->endElement(); // row
                            }
                        } elseif (\is_array($value)) {
                            foreach ($value as $item) {
                                $xml->startElement('item');
                                $xml->text(htmlspecialchars($item));
                                $xml->endElement(); // item
                            }
                        }
                    } else {
                        $xml->writeAttribute('label', $label);
                        $xml->text(htmlspecialchars($value));
                    }

                    $xml->endElement(); // $handle
                }

                $xml->endElement(); // submission
                fwrite($resource, $xml->flush());
            }
        }

        $xml->endElement(); // root
        $xml->endDocument();

        fwrite($resource, $xml->flush());
    }

    public function exportOld($resource): void
    {
        $xml = new \SimpleXMLElement('<root/>');

        foreach ($this->getRowBatch() as $rows) {
            $submission = $xml->addChild('submission');

            foreach ($rows as $columns) {
                foreach ($columns as $column) {
                    $field = $column->getField();
                    $value = $column->getValue();

                    if ($field instanceof MultiValueInterface) {
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
        }
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
