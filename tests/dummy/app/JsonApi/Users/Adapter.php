<?php

namespace App\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\HasOne;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $with = ['phone'];

    /**
     * @var array
     */
    protected $relationships = ['phone'];

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

    /**
     * @return HasOne
     */
    protected function phone()
    {
        return $this->hasOne();
    }

    /**
     * @inheritdoc
     */
    protected function deserializeAttribute($value, $resourceKey, $record)
    {
        if ('password' === $resourceKey) {
            return $value ? bcrypt($value) : null;
        }

        return parent::deserializeAttribute($value, $resourceKey, $record);
    }

}
