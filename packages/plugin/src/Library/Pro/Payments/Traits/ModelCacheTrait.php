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

namespace Solspace\Freeform\Library\Pro\Payments\Traits;

use craft\base\Model;

trait ModelCacheTrait
{
    /**
     * Model cache store.
     *
     * @var Model[]
     */
    protected $modelCache;

    /**
     * Saves model to cache.
     */
    protected function cacheSave(Model $model)
    {
        $this->modelCache[$model->id] = $model;
    }

    /**
     * Deletes cached model from cache.
     */
    protected function cacheDelete(int $id)
    {
        if (isset($this->modelCache[$id])) {
            unset($this->modelCache[$id]);
        }
    }

    /**
     * Resets cache.
     */
    protected function cacheClear()
    {
        $this->modelCache = [];
    }

    /**
     * Returns cached model or null if id not cached.
     *
     * @return null|Model
     */
    protected function cacheGet(int $id)
    {
        return $this->modelCache[$id] ?? null;
    }
}
