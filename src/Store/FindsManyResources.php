<?php

namespace CloudCreativity\LaravelJsonApi\Store;

use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

trait FindsManyResources
{

    /**
     * @return string
     */
    protected function getFindManyKey()
    {
        $key = property_exists($this, 'findManyFilter') ? $this->findManyFilter : null;

        return $key ?: DocumentInterface::KEYWORD_ID;
    }

    /**
     * Do the filters contain a `find-many` parameter?
     *
     * @param Collection $filters
     * @return bool
     */
    protected function isFindMany(Collection $filters)
    {
        return $filters->has($this->getFindManyKey());
    }

    /**
     * @param Collection $filters
     * @return array
     */
    protected function extractIds(Collection $filters)
    {
        $ids = $filters->get($this->getFindManyKey());

        return $this->normalizeIds($ids);
    }

    /**
     * @param $ids
     * @return array
     */
    protected function normalizeIds($ids)
    {
        return is_array($ids) ? $ids : explode(',', (string) $ids);
    }
}
