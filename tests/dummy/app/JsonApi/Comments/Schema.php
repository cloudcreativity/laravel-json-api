<?php

namespace DummyApp\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use DummyApp\Comment;

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
            'commentable' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['commentable']) ?
                    $resource->commentable : $this->createBelongsToIdentity($resource, 'commentable'),
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
