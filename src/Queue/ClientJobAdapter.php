<?php

namespace CloudCreativity\LaravelJsonApi\Queue;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use Illuminate\Support\Collection;

class ClientJobAdapter extends AbstractAdapter
{

    /**
     * ClientJobAdapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new ClientJob());
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
