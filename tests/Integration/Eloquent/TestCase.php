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

        $this->withDefaultApi(['prefix' => '/api/v1', 'as' => $this->routePrefix], function (ApiGroup $api) {
            $api->resource('comments');
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
            $api->resource('users');
        });
    }

    /**
     * Return the prefix for route names for the resources we're testing...
     *
     * @return string
     */
    protected function getRoutePrefix()
    {
        return 'api-v1::';
    }
}
