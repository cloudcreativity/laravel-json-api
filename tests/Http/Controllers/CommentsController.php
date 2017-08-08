<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi\Comments\Hydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class CommentsController extends EloquentController
{

    /**
     * CommentsController constructor.
     *
     * @param Hydrator $hydrator
     */
    public function __construct(Hydrator $hydrator)
    {
        parent::__construct(new Comment(), $hydrator);
    }
}
