<?php

namespace DummyApp\JsonApi\Phones;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use DummyApp\Phone;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Phone());
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
