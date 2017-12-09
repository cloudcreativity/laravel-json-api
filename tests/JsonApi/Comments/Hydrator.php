<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use Illuminate\Support\Facades\Auth;

class Hydrator extends EloquentHydrator
{

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
        'commentable',
    ];

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $record = new Comment();
        $record->user()->associate(Auth::user());

        return $record;
    }

}
