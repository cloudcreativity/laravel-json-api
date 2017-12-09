<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends EloquentAdapter
{

    /**
     * @var array
     */
    protected $relationships = [
        'taggables' => ['posts', 'videos'],
    ];

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Tag());
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

    /**
     * @inheritDoc
     */
    protected function isSearchOne(Collection $filters)
    {
        // TODO: Implement isSearchOne() method.
    }


}
