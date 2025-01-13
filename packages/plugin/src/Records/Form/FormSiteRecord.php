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

namespace Solspace\Freeform\Records\Form;

use craft\db\ActiveRecord;
use craft\models\Site;
use Solspace\Freeform\Records\FormRecord;

/**
 * @property int       $id
 * @property int       $formId
 * @property int       $siteId
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FormSiteRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms_sites}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public static function updateSitesForForm(int $formId, array $siteIds): void
    {
        $deletable = self::find()
            ->where(['formId' => $formId])
            ->andWhere(['not', ['siteId' => $siteIds]])
            ->all()
        ;

        foreach ($deletable as $record) {
            $record->delete();
        }

        $existingSiteIds = self::find()
            ->select('siteId')
            ->where(['formId' => $formId])
            ->column()
        ;

        $newIds = array_diff($siteIds, $existingSiteIds);

        foreach ($newIds as $newId) {
            $record = new self();
            $record->formId = $formId;
            $record->siteId = $newId;
            $record->save();
        }

        $form = FormRecord::findOne(['id' => $formId]);
        $metadata = json_decode($form->metadata);
        $metadata->general->sites = array_map('strval', $siteIds);

        $form->metadata = json_encode($metadata);
        $form->save();
    }

    public function getSite(): ?Site
    {
        return \Craft::$app->sites->getSiteById($this->siteId);
    }

    public function rules(): array
    {
        return [
            [['formId', 'siteId'], 'required'],
        ];
    }
}
