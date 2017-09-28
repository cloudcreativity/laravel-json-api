<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'users';

    /**
     * @var array
     */
    protected $attributes = ['name'];
}
