<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue67;

use DummyApp\JsonApi\Posts\Schema as BaseSchema;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class Schema extends BaseSchema
{

    /**
     * @inheritdoc
     */
    public function getAttributes($resource)
    {
        throw new JsonApiException([], 500);
    }
}
