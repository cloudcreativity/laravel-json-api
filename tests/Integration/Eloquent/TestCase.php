<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Testing\InteractsWithModels;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    use InteractsWithModels;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('comments', [
                'has-one' => 'commentable',
            ]);
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
            $api->resource('users');
            $api->resource('videos');
            $api->resource('tags', [
                'has-many' => 'taggables',
            ]);
        });
    }
}
