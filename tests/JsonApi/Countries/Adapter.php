<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Countries;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\HasMany;
use CloudCreativity\LaravelJsonApi\Tests\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $relationships = ['users'];

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Country());
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

    /**
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany();
    }

    /**
     * @return HasMany
     */
    public function posts()
    {
        return $this->hasMany();
    }

}
