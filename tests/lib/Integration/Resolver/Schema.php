<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

use DummyApp\JsonApi\Posts\Schema as BaseSchema;

class Schema extends BaseSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'foobars';
}
