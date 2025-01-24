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

use Solspace\Freeform\Library\Codepack\Exceptions\FileObject\FileNotFoundException;

class TemplatesFileComponent extends AbstractFileComponent
{
    /** @var array */
    private static $modifiableFileExtensions = [
        'html',
        'twig',
    ];

    /**
     * If anything has to be done with a file once it's copied over
     * This method does it.
     *
     * @throws FileNotFoundException
     */
    public function postFileCopyAction(string $newFilePath, string $prefix = null)
    {
        if (!file_exists($newFilePath)) {
            throw new FileNotFoundException(
                sprintf('Could not find file: %s', $newFilePath)
            );
        }

        $extension = strtolower(pathinfo($newFilePath, \PATHINFO_EXTENSION));

        // Prevent from editing anything other than css and js files
        if (!\in_array($extension, self::$modifiableFileExtensions, true)) {
            return;
        }

        $content = file_get_contents($newFilePath);

        $content = $this->updateSrcAndHref($content, $prefix);
        $content = $this->updateLinks($content, $prefix);
        $content = $this->updateTemplateCalls($content, $prefix);
        $content = $this->replaceCustomPrefixCalls($content, $prefix);
        $content = $this->offsetSegments($content, $prefix);

        file_put_contents($newFilePath, $content);
    }

    protected function getInstallDirectory(): string
    {
        return \Craft::$app->path->getSiteTemplatesPath();
    }

    protected function getTargetFilesDirectory(): string
    {
        return 'templates';
    }

    /**
     * This pattern matches all src or href tag values which begin with:
     * /css or /js or /images
     * And replaces it with the prefixed asset path.
     */
    private function updateSrcAndHref(string $content, string $prefix): string
    {
        $pattern = '/(src|href)=([\'"](?:\{{2}\s*siteUrl\s*}{2})?(?:\/?assets\/))demo\//';
        $replace = '$1=$2'.$prefix.'/';

        return (string) preg_replace($pattern, $replace, $content);
    }

    /**
     * Replaces all links that starts with "{{ siteUrl }}demo/" with the new path.
     */
    private function updateLinks(string $content, string $prefix): string
    {
        $pattern = '/([\'"](?:\{{2}\s*siteUrl\s*}{2})?\/?)demo\//';
        $replace = '$1'.$prefix.'/';

        return (string) preg_replace($pattern, $replace, $content);
    }

    /**
     * Updates all includes and extends with the new location.
     */
    private function updateTemplateCalls(string $content, string $prefix): string
    {
        $pattern = '/(\{\%\s*(?:extends|include)) ([\'"])(\/?)demo\//';
        $replace = '$1 $2$3'.$prefix.'/';

        return (string) preg_replace($pattern, $replace, $content);
    }

    /**
     * Offset all segments by the number of segments the $prefix has
     * since our demo templates will be at least 1 folder deep.
     */
    private function offsetSegments(string $content, string $prefix): string
    {
        $segmentCount = substr_count($prefix, '/') + 1;

        return str_replace(
            '{% set baseUrlSegments = 1 %}',
            "{% set baseUrlSegments = {$segmentCount} %}",
            $content
        );
    }

    private function replaceCustomPrefixCalls(string $content, string $prefix): string
    {
        $pattern = '#(%prefix%)#';

        return (string) preg_replace($pattern, $prefix, $content);
    }
}
