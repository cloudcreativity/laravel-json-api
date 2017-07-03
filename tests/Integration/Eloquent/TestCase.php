<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Testing\InteractsWithModels;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    use InteractsWithModels;

    /**
     * @var string
     */
    protected $routePrefix = 'api-v1::';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->withDefaultApi(['as' => $this->routePrefix], function (ApiGroup $api) {
            $api->resource('comments');
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
            $api->resource('users');
        });
    }
}
