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

namespace Solspace\Freeform\Library\Codepack\Components;

use craft\db\Query;
use craft\helpers\Json;
use craft\services\ProjectConfig;

class RoutesComponent extends AbstractJsonComponent
{
    /**
     * Calls the installation of this component.
     *
     * @param string $prefix
     */
    public function install(string $prefix = null)
    {
        $routeService = \Craft::$app->routes;

        $existingRoutes = [];
        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            /** @var ProjectConfig $config */
            $config = \Craft::$app->getProjectConfig();
            $existingRoutes = $config->get('routes');
        }

        $data = $this->getData();
        $demoFolder = $prefix.'/';

        foreach ($data as $route) {
            if (isset($route->urlParts, $route->template) && \is_array($route->urlParts)) {
                $urlParts = $route->urlParts;

                array_walk_recursive($urlParts, function (&$value) {
                    $value = stripslashes($value);
                });

                $urlParts[0] = $demoFolder.$urlParts[0];

                $pattern = '/(\\/?)(.*)/';
                $template = preg_replace($pattern, "$1{$demoFolder}$2", $route->template, 1);

                // Compile the URI parts into a regex pattern
                $uriPattern = '';
                $uriParts = array_filter($urlParts);
                $subpatternNameCounts = [];

                foreach ($uriParts as $part) {
                    if (\is_string($part)) {
                        $uriPattern .= preg_quote($part, '/');
                    } elseif (\is_array($part)) {
                        // Is the name a valid handle?
                        if (preg_match('/^[a-zA-Z]\w*$/', $part[0])) {
                            $subpatternName = $part[0];
                        } else {
                            $subpatternName = 'any';
                        }

                        // Make sure it's unique
                        if (isset($subpatternNameCounts[$subpatternName])) {
                            ++$subpatternNameCounts[$subpatternName];

                            // Append the count to the end of the name
                            $subpatternName .= $subpatternNameCounts[$subpatternName];
                        } else {
                            $subpatternNameCounts[$subpatternName] = 1;
                        }

                        // Add the var as a named subpattern
                        $uriPattern .= '<'.preg_quote($subpatternName, '/').':'.$part[1].'>';
                    }
                }

                if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
                    $uuid = $this->findExistingRoute($uriParts, $existingRoutes);
                    $routeService->saveRoute($urlParts, $template, null, $uuid);
                } else {
                    $id = (new Query())
                        ->select('id')
                        ->from('{{%routes}}')
                        ->where(['uriParts' => Json::encode($uriParts)])
                        ->scalar()
                    ;

                    $routeService->saveRoute($urlParts, $template, null, $id ?: null);
                }
            }
        }
    }

    /**
     * This is the method that sets all vital properties
     * ::$fileName.
     */
    protected function setProperties()
    {
        $this->fileName = 'routes.json';
    }

    /**
     * @return null|int|string
     */
    private function findExistingRoute(array $uriParts, array $existingRoutes = null)
    {
        if (!$existingRoutes) {
            return null;
        }

        foreach ($existingRoutes as $uuid => $route) {
            if (!isset($route['uriParts'])) {
                continue;
            }

            if (Json::encode($route['uriParts']) === Json::encode($uriParts)) {
                return $uuid;
            }
        }

        return null;
    }
}
