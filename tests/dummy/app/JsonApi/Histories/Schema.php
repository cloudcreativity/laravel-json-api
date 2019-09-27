<?php

namespace DummyApp\JsonApi\Histories;

use DummyApp\History;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'histories';

    /**
     * @param History $resource
     * @return string
     */
    public function getId($resource)
    {
        return (string) $resource->getRouteKey();
    }

    /**
     * @param History $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        return ['detail' => $resource->detail];
    }

    /**
     * @param History $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'user' => [
                self::SHOW_SELF => false,
                self::SHOW_RELATED => false,
                self::SHOW_DATA => isset($includeRelationships['user']),
                self::DATA => static function () use ($resource) {
                    return $resource->user;
                },
            ],
        ];
    }


}
