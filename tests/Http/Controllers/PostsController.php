<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts\Hydrator;

class PostsController extends EloquentController
{

    /**
     * @var string
     */
    protected $hydrator = Hydrator::class;

}
