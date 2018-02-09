<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
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

}
