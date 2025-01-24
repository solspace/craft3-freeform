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

namespace Solspace\Freeform\Models;

use craft\base\Model;
use craft\helpers\UrlHelper;
use Solspace\Freeform\Form\Types\Regular;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Composer\Components\Layout;
use Solspace\Freeform\Library\Composer\Composer;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use Solspace\Freeform\Library\Translations\CraftTranslator;

/**
 * Class Freeform_FormModel.
 *
 * @property int    $id
 * @property string $uid
 * @property string $name
 * @property string $handle
 * @property int    $spamBlockCount
 * @property string $submissionTitleFormat
 * @property string $description
 * @property string $layoutJson
 * @property string $returnUrl
 * @property string $extraPostUrl
 * @property string $extraPostTriggerPhrase
 * @property int    $defaultStatus
 * @property int    $formTemplateId
 * @property string $optInDataStorageTargetHash
 * @property string $limitFormSubmissions
 * @property string $color
 * @property bool   $gtmEnabled
 * @property string $gtmId
 * @property string $gtmEventName
 */
class FormModel extends Model
{
    /** @var int */
    public $id;

    /** @var string */
    public $uid;

    /** @var string */
    public $type;

    /** @var array */
    public $metadata;

    /** @var string */
    public $name;

    /** @var string */
    public $handle;

    /** @var int */
    public $spamBlockCount;

    /** @var string */
    public $submissionTitleFormat;

    /** @var string */
    public $description;

    /** @var string */
    public $layoutJson;

    /** @var string */
    public $returnUrl;

    /** @var string */
    public $extraPostUrl;

    /** @var string */
    public $extraPostTriggerPhrase;

    /** @var int */
    public $defaultStatus;

    /** @var int */
    public $formTemplateId;

    /** @var string */
    public $optInDataStorageTargetHash;

    /** @var string */
    public $limitFormSubmissions;

    /** @var string */
    public $color;

    /** @var bool */
    public $gtmEnabled;

    /** @var string */
    public $gtmId;

    /** @var string */
    public $gtmEventName;

    /** @var int[] */
    private static $spamBlockCountCache;

    /** @var Composer */
    private $composer;

    public static function create(): self
    {
        $form = new self();
        $form->spamBlockCount = 0;
        $form->type = Regular::class;
        $form->metadata = [];

        return $form;
    }

    public function setLayout(Composer $composer)
    {
        $form = $composer->getForm();
        $this->name = $form->getName();
        $this->handle = $form->getHandle();
        $this->type = \get_class($form);
        $this->submissionTitleFormat = $form->getSubmissionTitleFormat();
        $this->description = $form->getDescription();
        $this->defaultStatus = $form->getDefaultStatus();
        $this->returnUrl = $form->getReturnUrl();
        $this->extraPostUrl = $form->getExtraPostUrl();
        $this->extraPostTriggerPhrase = $form->getExtraPostTriggerPhrase();
        $this->color = $form->getColor();
        $this->optInDataStorageTargetHash = $form->getOptInDataStorageTargetHash();
        $this->limitFormSubmissions = $form->getLimitFormSubmissions();
        $this->layoutJson = $composer->getComposerStateJSON();
        $this->gtmEnabled = $form->isGtmEnabled();
        $this->gtmId = $form->getGtmId();
        $this->gtmEventName = $form->getGtmEventName();
    }

    public function getComposer(bool $refresh = false): Composer
    {
        if (null === $this->composer || $refresh) {
            return $this->composer = new Composer(
                $this,
                json_decode($this->layoutJson, true),
                new CraftTranslator(),
                FreeformLogger::getInstance(FreeformLogger::FORM)
            );
        }

        return $this->composer;
    }

    public function getBlockedSpamCount(): int
    {
        if (!isset(self::$spamBlockCountCache[$this->id])) {
            $spamBlockCount = $this->spamBlockCount;

            if (Freeform::getInstance()->settings->isSpamFolderEnabled()) {
                $spamBlockCount = Freeform::getInstance()->spamSubmissions->getSubmissionCount([$this->id], null, true);
            }

            self::$spamBlockCountCache[$this->id] = $spamBlockCount;
        }

        return self::$spamBlockCountCache[$this->id];
    }

    public function getLayout(): Layout
    {
        return $this->getComposer()->getForm()->getLayout();
    }

    public function getForm(bool $refresh = false): Form
    {
        return $this->getComposer($refresh)->getForm();
    }

    public function getLayoutAsJson(): string
    {
        return $this->getComposer()->getComposerStateJSON();
    }

    public function isEditable(): bool
    {
        return true;
    }

    public function getCpEditUrl()
    {
        return UrlHelper::cpUrl('freeform/forms/'.$this->id);
    }
}
