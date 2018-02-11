<?php

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;

JsonApi::register('default', [], function (ApiGroup $api) {
    $api->resource('comments', [
        'has-one' => 'commentable',
    ]);
    $api->resource('countries', [
        'has-many' => ['users', 'posts'],
    ]);
    $api->resource('posts', [
        'has-one' => 'author',
        'has-many' => ['comments', 'tags'],
    ]);
    $api->resource('users', [
        'has-one' => 'phone',
    ]);
    $api->resource('videos');
    $api->resource('tags', [
        'has-many' => 'taggables',
    ]);

    $api->resource('sites');
});
