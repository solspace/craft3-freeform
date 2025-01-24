<?php

namespace Solspace\Freeform\Library\DataObjects\Summary\Statistics;

class Settings
{
    public bool $customPluginName = false;
    public bool $defaultView = false;
    public bool $renderHtmlInComposer = false;
    public bool $ajaxEnabledByDefault = false;
    public bool $includeDefaultFormattingTemplates = false;
    public bool $removeNewlinesOnExport = false;
    public bool $populateValuesFromGet = false;
    public bool $disableSubmit = false;
    public bool $autoScroll = false;
    public string $jsInsertLocation = '';
    public string $jsInsertType = '';
    public string $sessionContextType = '';
    public bool $purgeSubmissions = false;
    public ?int $purgeInterval = null;
    public bool $purgeAssets = false;
    public ?int $purgeAssetsInterval = null;
    public bool $formattingTemplatesPath = false;
    public bool $sendAlertsOnFailedNotifications = false;
    public bool $notificationTemplatesPath = false;
    public bool $successTemplatesPath = false;
    public bool $modifiedStatuses = false;
    public bool $demoTemplatesInstalled = false;
}
