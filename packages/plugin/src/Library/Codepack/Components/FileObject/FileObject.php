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

use Solspace\Freeform\Library\Codepack\Exceptions\FileObject\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

abstract class FileObject
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var bool */
    protected $folder;

    abstract protected function __construct(string $path);

    /**
     * @throws FileNotFoundException
     */
    public static function createFromPath(string $path): self
    {
        $realPath = realpath($path);

        if (!$realPath) {
            throw new FileNotFoundException(
                sprintf('Path points to nothing: "%s"', $path)
            );
        }

        $isFolder = is_dir($realPath);

        return $isFolder ? new Folder($realPath) : new File($realPath);
    }

    /**
     * Copy the file or directory to $target location.
     *
     * @param null|array|callable $callable
     */
    abstract public function copy(string $target, string $prefix = null, callable $callable = null, string $filePrefix = null);

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isFolder(): bool
    {
        return $this->folder;
    }

    protected function getFileSystem(): Filesystem
    {
        static $fileSystem;

        if (null === $fileSystem) {
            $fileSystem = new Filesystem();
        }

        return $fileSystem;
    }

    protected function getFinder(): Finder
    {
        return new Finder();
    }
}
