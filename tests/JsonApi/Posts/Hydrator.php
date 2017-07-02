<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;

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
}
