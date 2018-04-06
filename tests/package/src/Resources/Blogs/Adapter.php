<?php

namespace DummyPackage\Resources\Blogs;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use DummyPackage\Blog;
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
    protected function filter($query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
