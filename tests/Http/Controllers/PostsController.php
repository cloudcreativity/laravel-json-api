<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Posts\Hydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;

class PostsController extends EloquentController
{

    /**
     * PostsController constructor.
     *
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Post(), $hydrator);
    }
}
