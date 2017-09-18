<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Tests\Entities\Site;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Sites;

class SitesController extends JsonApiController
{

    /**
     * @var string
     */
    protected $hydrator = Sites\Hydrator::class;

    /**
     * @var bool
     */
    protected $useTransactions = false;

    /**
     * @param Site $record
     * @return bool
     */
    protected function destroyRecord($record)
    {
        $record->delete();

        return true;
    }

}
