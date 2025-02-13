<?php

namespace Solspace\Freeform\Bundles\Form\Limiting;

use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\records\Element;
use craft\records\Session;
use Solspace\Freeform\Bundles\Form\Context\Request\EditSubmissionContext;
use Solspace\Freeform\Bundles\Form\Tracking\Cookies;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\FormEventInterface;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Fields\Implementations\EmailField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class FormLimiting extends FeatureBundle
{
    public const NO_LIMIT = 'no_limit';
    public const NO_LIMIT_LOGGED_IN_USERS_ONLY = 'no_limit_logged_in_users_only';
    public const LIMIT_ONCE_PER_LOGGED_IN_USERS_ONLY = 'limit_once_per_logged_in_user_only';
    public const LIMIT_ONCE_PER_EMAIL = 'limit_once_per_email';
    public const LIMIT_ONCE_PER_USER_OR_COOKIE = 'limit_once_per_user_or_cookie';
    public const LIMIT_ONCE_PER_USER_OR_IP_OR_COOKIE = 'limit_once_per_user_or_ip_or_cookie';

    private array $formCache = [];

    public function __construct()
    {
        Event::on(Form::class, Form::EVENT_FORM_LOADED, [$this, 'handleDuplicateCheck']);
        Event::on(Form::class, Form::EVENT_RENDER_AFTER_CLOSING_TAG, [$this, 'handleDuplicateCheck']);
        Event::on(Form::class, Form::EVENT_PERSIST_STATE, [$this, 'handleDuplicateCheck']);
        Event::on(Form::class, Form::EVENT_BEFORE_VALIDATE, [$this, 'handleDuplicateCheck']);
    }

    public function handleDuplicateCheck(FormEventInterface $event): void
    {
        $form = $event->getForm();
        $settings = $form->getSettings();
        $behaviorSettings = $settings->getBehavior();

        $duplicateCheck = $behaviorSettings->duplicateCheck;

        $token = EditSubmissionContext::getToken($form);
        if ($token) {
            return;
        }

        if (self::NO_LIMIT === $duplicateCheck) {
            return;
        }

        if (self::NO_LIMIT_LOGGED_IN_USERS_ONLY === $duplicateCheck) {
            $this->limitByLoggedInUsersOnly($event);
        }

        if (self::LIMIT_ONCE_PER_LOGGED_IN_USERS_ONLY === $duplicateCheck) {
            $this->limitBySession($event);
        }

        if (self::LIMIT_ONCE_PER_EMAIL === $duplicateCheck) {
            $this->limitByEmail($event);
        }

        if (self::LIMIT_ONCE_PER_USER_OR_COOKIE === $duplicateCheck) {
            $this->limitByUserId($event);
            $this->limitByCookie($event);
        }

        if (self::LIMIT_ONCE_PER_USER_OR_IP_OR_COOKIE === $duplicateCheck) {
            $this->limitByUserId($event);
            $this->limitByCookie($event);
            $this->limitByIp($event);
        }
    }

    private function limitByLoggedInUsersOnly(FormEventInterface $event): void
    {
        $userId = \Craft::$app->user->getId();
        if ($userId) {
            return;
        }

        $this->addMessage($event);
    }

    private function limitByEmail(FormEventInterface $event): void
    {
        $request = \Craft::$app->getRequest();
        if ($request->getIsCpRequest() || $request->getIsConsoleRequest()) {
            return;
        }

        $form = $event->getForm();

        // Get all email fields on the form
        $emailFields = $form->getLayout()->getFields(EmailField::class);

        // Get all email field values
        $emailFieldValues = [];
        foreach ($emailFields as $emailField) {
            $value = $request->post($emailField->getHandle());
            if (!empty($value)) {
                $emailFieldValues[] = '"'.$value.'"';
            }
        }

        // If no email field values, bail
        if (empty($emailFieldValues)) {
            return;
        }

        // Builds an SQL query that checks existing email field values against submitted email field values
        // E.g sc.`email_field` IN ('foo@example.com', 'bar@example.com')
        // E.g sc.`email_field` IN ('foo@example.com', 'bar@example.com') OR sc.`another_email_field` IN ('foo@example.com', 'bar@example.com')
        $emailFieldQuery = [];
        $emailFieldValues = '('.implode(', ', $emailFieldValues).')';
        foreach ($emailFields as $emailField) {
            $emailFieldQuery[] = 'sc.[['.Submission::getFieldColumnName($emailField).']] IN '.$emailFieldValues;
        }
        $emailFieldQuery = implode(' OR ', $emailFieldQuery);

        $elements = Table::ELEMENTS;
        $submissions = Submission::TABLE;
        $submissionsContents = Submission::getContentTableName($form);

        $query = (new Query())
            ->select(['s.[[id]]'])
            ->from("{$submissions} s")
            ->innerJoin(
                "{$elements} e",
                'e.[[id]] = s.[[id]]'
            )
            ->innerJoin("{$submissionsContents} sc", 'sc.[[id]] = s.[[id]]')
            ->where([
                's.[[isSpam]]' => false,
                's.[[formId]]' => $form->getId(),
                'e.[[dateDeleted]]' => null,
            ])
            ->andWhere($emailFieldQuery)
            ->limit(1)
        ;

        $isPosted = (bool) $query->scalar();

        if ($isPosted) {
            $this->addMessage($event);
        }
    }

    private function limitByCookie(FormEventInterface $event): void
    {
        $name = Cookies::getCookieName($event->getForm());
        $cookie = $_COOKIE[$name] ?? null;

        if ($cookie) {
            $this->addMessage($event);
        }
    }

    private function limitByIp(FormEventInterface $event): void
    {
        $request = \Craft::$app->getRequest();
        if ($request->getIsCpRequest() || $request->getIsConsoleRequest()) {
            return;
        }

        $form = $event->getForm();
        $settings = $form->getSettings();
        $generalSettings = $settings->getGeneral();

        if (!$generalSettings->collectIpAddresses) {
            return;
        }

        $submissions = Submission::TABLE;
        $query = (new Query())
            ->select(["{$submissions}.[[id]]"])
            ->from($submissions)
            ->where([
                'isSpam' => false,
                'formId' => $event->getForm()->getId(),
                'ip' => $request->getUserIP(),
            ])
            ->limit(1)
        ;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Element::tableName();
            $query->innerJoin(
                $elements,
                "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
            );
        }

        $isPosted = (bool) $query->scalar();

        if ($isPosted) {
            $this->addMessage($event);
        }
    }

    private function limitBySession(FormEventInterface $event): void
    {
        $userId = \Craft::$app->getUser()->getId();
        $session = Session::find()->where(['userId' => $userId])->orderBy('dateUpdated desc')->one();

        if (!$userId || !$session) {
            $this->addMessage($event);

            return;
        }

        $form = $event->getForm();

        $elements = Element::tableName();
        $submissions = Submission::TABLE;

        $query = (new Query())
            ->select(["{$submissions}.[[id]]"])
            ->from($submissions)
            ->where([
                'isSpam' => false,
                'formId' => $form->getId(),
                'userId' => $userId,
            ])
            ->limit(1)
            ->innerJoin(
                $elements,
                "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
            )
        ;

        $isPosted = (bool) $query->scalar();

        $userSessionDuration = \Craft::$app->getConfig()->getGeneral()->userSessionDuration;

        if ($isPosted && DateTimeHelper::isWithinLast($session->dateUpdated, $userSessionDuration.' seconds')) {
            $this->addMessage($event);
        }
    }

    private function limitByUserId(FormEventInterface $event): void
    {
        $userId = \Craft::$app->user->getId();
        if (!$userId) {
            return;
        }

        $submissions = Submission::TABLE;
        $query = (new Query())
            ->select(["{$submissions}.[[id]]"])
            ->from($submissions)
            ->where([
                'isSpam' => false,
                'formId' => $event->getForm()->getId(),
                'userId' => $userId,
            ])
            ->limit(1)
        ;

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Element::tableName();
            $query->innerJoin(
                $elements,
                "{$elements}.[[id]] = {$submissions}.[[id]] AND {$elements}.[[dateDeleted]] IS NULL"
            );
        }

        $isPosted = (bool) $query->scalar();
        if ($isPosted) {
            $this->addMessage($event);
        }
    }

    private function addMessage(FormEventInterface $event): void
    {
        $form = $event->getForm();
        $formId = $form->getId();

        // Triggered during from validation
        if ($event instanceof ValidationEvent) {
            if (\in_array($formId, $this->formCache, true)) {
                return;
            }

            $form->addError(Freeform::t("Sorry, you've already submitted this form."));

            $this->formCache[] = $formId;
        }

        $form->setDuplicate(true);
    }
}
