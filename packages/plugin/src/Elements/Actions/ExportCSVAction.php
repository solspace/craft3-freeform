<?php

namespace Solspace\Freeform\Elements\Actions;

use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;

class ExportCSVAction extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Freeform::t('Export to CSV');
    }

    public function getTriggerHtml(): ?string
    {
        $type = Json::encode(static::class);

        $js = <<<EOT
            (function()
            {
            	var trigger = new Craft.ElementActionTrigger({
            		handle: 'Freeform_ExportCSV',
            		batch: true,
            		type: {$type},
            		activate: function(\$selectedItems)
            		{
            		    var ids = [];
            		    \$selectedItems.each(function() {
            		        ids.push($(this).data("id"));
            		    });

            			var form = $('<form method="post" target="_blank" action="">' +
            			'<input type="hidden" name="action" value="freeform/submissions/export" />' +
            			'<input type="hidden" name="submissionIds" value="' + ids.join(",") + '" />' +
            			'<input type="hidden" name="{csrfName}" value="{csrfValue}" />' +
            			'<input type="submit" value="Submit" />' +
            			'</form>');

            			form.appendTo('body');
            			form.submit();
            			form.remove();
            		}
            	});
            })();
            EOT;

        $js = str_replace(
            ['{csrfName}', '{csrfValue}'],
            [\Craft::$app->config->general->csrfTokenName, \Craft::$app->request->getCsrfToken()],
            $js
        );

        \Craft::$app->view->registerJs($js);

        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $descriptors = new FieldDescriptorCollection();
        $submission = $query->one();
        if (!$submission instanceof Submission) {
            throw new FreeformException(Freeform::t('No submissions found'));
        }

        $form = $submission->getForm();
        foreach ($submission as $key => $value) {
            if (!$value instanceof FieldInterface) {
                $descriptors->add(new FieldDescriptor($key, ucfirst($key)));
            }
        }

        foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
            $descriptors->add(new FieldDescriptor($field->getId(), $field->getLabel()));
        }

        $exporter = new ExportCsv(
            $form,
            $query,
            $descriptors,
            Freeform::getInstance()->exportProfiles->getExportSettings(),
        );

        $fileName = \sprintf('%s submissions %s.csv', $form->name, date('Y-m-d H:i'));

        Freeform::getInstance()->exportProfiles->outputFile($exporter->export(), $fileName, $exporter->getMimeType());

        return true;
    }
}
