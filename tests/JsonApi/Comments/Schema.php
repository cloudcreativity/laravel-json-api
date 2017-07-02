<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    /**
     * @var array
     */
    protected $attributes = [
        'content'
    ];

    /**
     * @param Comment $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'post' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['post']) ?
                    $resource->post : $this->createBelongsToIdentity($resource, 'post'),
            ],
            'created-by' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['created-by']) ?
                    $resource->person : $this->createBelongsToIdentity($resource, 'user'),
            ],
        ];
    }
}
