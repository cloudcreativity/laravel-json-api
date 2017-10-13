<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use Illuminate\Support\Facades\Auth;

class Hydrator extends EloquentHydrator
{

    /**
     * @var bool
     */
    protected $clientId = true;

    /**
     * @var array
     */
    protected $attributes = [
        'content',
    ];

    /**
     * @var array
     */
    protected $relationships = [
        'post',
    ];

    /**
     * @param ResourceObjectInterface $resource
     * @param Comment $record
     */
    protected function hydrating(ResourceObjectInterface $resource, $record)
    {
        if (!$record->exists) {
            $record->user()->associate(Auth::user());
        }
    }
}
