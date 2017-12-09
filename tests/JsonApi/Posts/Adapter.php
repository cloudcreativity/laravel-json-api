<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\HasMany;
use CloudCreativity\LaravelJsonApi\Eloquent\HasOne;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends EloquentAdapter
{

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

    /**
     * @inheritDoc
     */
    protected function isSearchOne(Collection $filters)
    {
        // TODO: Implement isSearchOne() method.
    }

    /**
     * @return HasOne
     */
    protected function author()
    {
        return $this->hasOne();
    }

    /**
     * @return HasMany
     */
    protected function comments()
    {
        return $this->hasMany();
    }

    /**
     * @return HasMany
     */
    protected function tags()
    {
        return $this->hasMany();
    }

}
