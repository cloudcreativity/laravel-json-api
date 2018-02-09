<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Videos\Hydrator;

class VideosController extends JsonApiController
{

    /**
     * @var string
     */
    protected $hydrator = Hydrator::class;
}
