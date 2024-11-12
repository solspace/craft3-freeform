<?php

namespace Solspace\Freeform\Library\Helpers;

use craft\models\Site;
use Solspace\Freeform\Freeform;

class SitesHelper
{
    public static function getPostedSite(): ?Site
    {
        if (\Craft::$app->request->isConsoleRequest) {
            return null;
        }

        $site = null;
        $query = \Craft::$app->request->get('site');
        if ($query) {
            if (is_numeric($query)) {
                $site = \Craft::$app->sites->getSiteById($query);
            } else {
                $site = \Craft::$app->sites->getSiteByHandle($query);
            }
        }

        return $site;
    }

    public static function getCurrentCpSite(): ?Site
    {
        if (!self::isEnabled()) {
            return null;
        }

        $site = null;

        if (!\Craft::$app->request->isConsoleRequest) {
            $query = \Craft::$app->request->getQueryParam('site');
            if ($query) {
                $site = \Craft::$app->sites->getSiteByHandle($query);
            }
        }

        if (!$site) {
            $site = \Craft::$app->sites->getCurrentSite();
        }

        return $site;
    }

    public static function getCurrentCpPageSiteId(): ?int
    {
        return self::getCurrentCpSite()?->id;
    }

    public static function getCurrentCpPageSiteHandle(): ?string
    {
        return self::getCurrentCpSite()?->handle;
    }

    public static function getFrontendSiteHandle(): ?string
    {
        if (!self::isEnabled()) {
            return null;
        }

        return \Craft::$app->sites->getCurrentSite()->handle;
    }

    public static function getSiteHandlesForFrontend(): ?array
    {
        if (!self::isEnabled()) {
            return null;
        }

        return [self::getFrontendSiteHandle()];
    }

    public static function getEditableSites(): array
    {
        return \Craft::$app->sites->getEditableSites();
    }

    public static function getEditableSiteHandles(): ?array
    {
        return array_map(
            fn ($site) => $site->handle,
            self::getEditableSites()
        );
    }

    public static function getEditableSiteIds(): ?array
    {
        return array_map(
            fn ($site) => $site->id,
            self::getEditableSites()
        );
    }

    public static function isEnabled(): bool
    {
        if (!Freeform::getInstance()->edition()->isAtLeast(Freeform::EDITION_PRO)) {
            return false;
        }

        return Freeform::getInstance()->settings->getSettingsModel()->sitesEnabled;
    }
}
