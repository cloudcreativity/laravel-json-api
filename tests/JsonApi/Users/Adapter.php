<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * @inheritDoc
     */
    protected function filter(Builder $query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
