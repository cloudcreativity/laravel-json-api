<?php

namespace DummyApp\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\MorphHasMany;
use DummyApp\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $relationships = [
        'taggables',
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
        return $this->morphMany(
            $this->hasMany('posts'),
            $this->hasMany('videos')
        );
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
