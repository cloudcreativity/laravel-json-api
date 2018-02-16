<?php

namespace DummyApp\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\HasOne;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\User;
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
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new User(), $paging);
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        if ($name = $filters->get('name')) {
            $query->where('users.name', 'like', "%{$name}%");
        }
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
