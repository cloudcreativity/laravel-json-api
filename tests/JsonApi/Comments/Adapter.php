<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\HasOne;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Adapter extends AbstractAdapter
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
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Comment());
    }

    /**
     * @return HasOne
     */
    protected function commentable()
    {
        return $this->hasOne();
    }

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $record = new Comment();
        $record->user()->associate(Auth::user());

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
