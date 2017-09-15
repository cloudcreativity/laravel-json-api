<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;

class Hydrator extends EloquentHydrator
{

    /**
     * @var array
     */
    protected $attributes = [
        'title',
        'slug',
        'content',
    ];

    /**
     * @var array
     */
    protected $relationships = [
        'author',
        'tags',
    ];

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        return new Post();
    }

}
