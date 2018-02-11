<?php

namespace App\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;

class Schema extends AbstractSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'tags';

    /**
     * @param object $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'taggables' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
            ],
        ];
    }
}
