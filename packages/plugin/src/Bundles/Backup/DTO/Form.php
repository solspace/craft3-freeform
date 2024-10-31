<?php

namespace Solspace\Freeform\Bundles\Backup\DTO;

use Solspace\Freeform\Bundles\Backup\Collections\FormIntegrationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\NotificationCollection;
use Solspace\Freeform\Bundles\Backup\Collections\PageCollection;
use Solspace\Freeform\Bundles\Backup\Collections\RulesCollection;
use Solspace\Freeform\Bundles\Backup\Collections\SitesCollection;
use Solspace\Freeform\Bundles\Backup\Collections\TranslationCollection;
use Solspace\Freeform\Form\Settings\Settings;

class Form
{
    public string $uid;
    public string $name;
    public string $handle;
    public int $order;

    public Settings $settings;

    public NotificationCollection $notifications;
    public FormIntegrationCollection $integrations;
    public RulesCollection $rules;
    public PageCollection $pages;

    public ?SitesCollection $sites = null;
    public ?TranslationCollection $translations = null;

    public function __construct()
    {
        $this->notifications = new NotificationCollection();
        $this->integrations = new FormIntegrationCollection();
        $this->rules = new RulesCollection();
        $this->pages = new PageCollection();
        $this->translations = new TranslationCollection();
    }
}
