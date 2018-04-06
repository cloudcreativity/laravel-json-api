<?php

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use DummyApp\Post;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var array
     */
    protected $attributes = [
        'title',
        'slug',
        'content',
        'published_at' => 'published',
    ];

    /**
     * @param Post $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['author']) ?
                    $resource->author : $this->createBelongsToIdentity($resource, 'author'),
            ],
            'comments' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
            ],
            'tags' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => function () use ($resource) {
                    return $resource->tags;
                },
            ],
        ];
    }
}
