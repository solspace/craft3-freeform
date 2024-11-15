<?php

namespace Solspace\Freeform\Library\Configuration;

use craft\models\Site;
use Solspace\Freeform\Bundles\Form\Limiting\LimitedUsers\LimitedUserChecker;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Services\SettingsService;

class FreeformConfig implements \JsonSerializable
{
    private const EXPRESS_LIMIT_FORMS = 1;
    private const EXPRESS_LIMIT_FIELDS = 20;

    private array $config = [];

    public function __construct(
        SettingsService $settings,
        LimitedUserChecker $limitedUserChecker,
    ) {
        $plugin = Freeform::getInstance();
        $settingsModel = $settings->getSettingsModel();
        $edition = $plugin->edition();

        $currentSiteId = SitesHelper::getCurrentCpPageSiteId();
        $sites = \Craft::$app->sites->getEditableSites();
        if (empty($sites)) {
            $sites = [\Craft::$app->sites->getCurrentSite()];
        }

        $this->config = [
            'templates' => [
                'native' => (bool) $settingsModel->defaults->includeSampleTemplates,
            ],
            'feed' => (bool) $settingsModel->displayFeed,
            'limits' => [
                'forms' => $edition->isAtLeast(Freeform::EDITION_LITE) ? 0 : self::EXPRESS_LIMIT_FORMS,
                'fields' => $edition->isAtLeast(Freeform::EDITION_LITE) ? 0 : self::EXPRESS_LIMIT_FIELDS,
            ],
            'metadata' => [
                'craft' => [
                    'is5' => version_compare(\Craft::$app->version, '5.0.0', '>='),
                    'version' => \Craft::$app->version,
                ],
                'freeform' => [
                    'version' => $plugin->getVersion(),
                    'canCreate' => PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_CREATE),
                    'canDelete' => PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_DELETE),
                ],
            ],
            'editions' => [
                'edition' => $plugin->edition,
                'tiers' => $plugin->edition()->getEditions(),
            ],
            'sites' => [
                'enabled' => SitesHelper::isEnabled(),
                'current' => $currentSiteId,
                'list' => array_map(
                    fn (Site $site) => [
                        'id' => $site->id,
                        'name' => $site->name,
                        'handle' => $site->handle,
                        'primary' => $site->primary,
                    ],
                    $sites,
                ),
            ],
            'limitations' => [
                'items' => $limitedUserChecker->getAll(),
            ],
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->config;
    }
}
