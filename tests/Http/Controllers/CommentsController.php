<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;

class CommentsController extends EloquentController
{

    /**
     * CommentsController constructor.
     */
    public function __construct()
    {
        parent::__construct(new Comment());
    }
}
