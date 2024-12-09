<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Transformers;

use Solspace\Freeform\Form\Form;

class ManifestTransformer
{
    public function __construct(
        private FormMonitorFieldTransformer $fieldTransformer,
    ) {}

    public function transform(Form $form): object
    {
        $manifest = [
            'form' => [
                'id' => $form->getId(),
                'uid' => $form->getUid(),
                'name' => $form->getName(),
                'handle' => $form->getHandle(),
                'settings' => $form->getSettings()->toArray(),
            ],
            'notifications' => [],
        ];

        $layout = [];
        foreach ($form->getLayout()->getPages() as $page) {
            $pageData = [];

            foreach ($page->getRows() as $row) {
                $rowData = [];

                foreach ($row->getFields() as $field) {
                    $rowData[] = $this->fieldTransformer->transform($field);
                }

                $pageData[] = $rowData;
            }

            $layout[] = $pageData;
        }

        $manifest['layout'] = $layout;

        return (object) $manifest;
    }
}
