<?php

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;

JsonApi::register('default', [], function (ApiGroup $api) {
    $api->resource('comments', [
        'has-one' => 'commentable',
    ]);
    $api->resource('countries', [
        'controller' => false,
        'has-many' => ['users', 'posts'],
    ]);
    $api->resource('posts', [
        'has-one' => [
            'author' => ['inverse' => 'users']
        ],
        'has-many' => ['comments', 'tags'],
    ]);
    $api->resource('users', [
        'controller' => false,
        'has-one' => 'phone',
    ]);
    $api->resource('videos', [
        'controller' => false,
    ]);
    $api->resource('tags', [
        'controller' => false,
        'has-many' => 'taggables',
    ]);

    $api->resource('sites');
});
