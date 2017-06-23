<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';
}
