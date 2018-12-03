<?php

namespace DummyApp\JsonApi\Downloads;

use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'downloads';

    /**
     * @inheritDoc
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
            'created-at' => $resource->created_at->toAtomString(),
            'updated-at' => $resource->updated_at->toAtomString(),
            'category' => $resource->category,
        ];
    }

}
