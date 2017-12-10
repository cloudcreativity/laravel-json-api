<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\MorphHasMany;
use CloudCreativity\LaravelJsonApi\Tests\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
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
     * @return MorphHasMany
     */
    protected function taggables()
    {
        return $this->morphMany('posts', 'videos');
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }


}
