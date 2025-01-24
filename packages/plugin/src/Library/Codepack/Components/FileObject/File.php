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

namespace Solspace\Freeform\Library\Codepack\Components\FileObject;

class File extends FileObject
{
    /**
     * File constructor.
     */
    protected function __construct(string $path)
    {
        $file = pathinfo($path, \PATHINFO_BASENAME);

        $this->folder = false;
        $this->path = $path;
        $this->name = $file;
    }

    /**
     * Copy the file or directory to $target location.
     *
     * @param null|array|callable $callable
     */
    public function copy(string $target, string $prefix = null, callable $callable = null, string $filePrefix = null)
    {
        $target = rtrim($target, '/');
        $newFilePath = $target.'/'.$filePrefix.$this->name;

        $this->getFileSystem()->copy($this->path, $newFilePath, true);

        if (null !== $callable) {
            $callable($newFilePath, $prefix);
        }
    }
}
