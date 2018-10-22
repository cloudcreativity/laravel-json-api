<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use Neomerx\JsonApi\Schema\SchemaProvider;

class ClientJobSchema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'queue-jobs';

    /**
     * @param ClientJob $resource
     * @return string
     */
    public function getId($resource)
    {
        return $resource->getRouteKey();
    }

    /**
     * @param ClientJob $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        return [
            'created-at' => $resource->created_at->toAtomString(),
            'resource' => $resource->resource_type,
            'updated-at' => $resource->updated_at->toAtomString(),
        ];
    }


}
