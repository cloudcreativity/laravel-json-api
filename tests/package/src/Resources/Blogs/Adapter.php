<?php

namespace Package\Resources\Blogs;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Package\Blog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Blog());
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
