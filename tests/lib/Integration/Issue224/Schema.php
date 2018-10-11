<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue224;

use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'endUsers';

    /**
     * @inheritdoc
     */
    public function getId($resource)
    {
        return $resource->getRouteKey();
    }

    /**
     * @inheritDoc
     */
    public function getAttributes($resource)
    {
        return [
            'name' => $resource->name,
        ];
    }

}
