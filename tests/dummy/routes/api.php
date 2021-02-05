<?php
/**
 * Copyright 2020 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;

JsonApi::register('v1', [], function (RouteRegistrar $api) {

    $api->resource('avatars', ['controller' => true]);

    $api->resource('comments', [
        'controller' => true,
        'middleware' => 'auth',
        'has-one' => 'commentable',
    ]);

    $api->resource('countries', [
        'has-many' => ['users', 'posts'],
    ]);


    $api->resource('downloads', [
        'async' => true,
    ]);

    $api->resource('posts', [
        'has-one' => [
            'author' => ['inverse' => 'users'],
            'image',
        ],
        'has-many' => [
            'comments',
            'tags',
            'related' => ['only' => ['read', 'related']],
            'related-video' => ['only' => ['read', 'related']],
        ],
    ]);

    $api->resource('users', [
        'has-one' => 'phone',
        'has-many' => 'roles',
    ]);

    $api->resource('videos');

    $api->resource('tags', [
        'has-many' => 'taggables',
    ]);

    $api->resource('sites', [
        'controller' => true,
    ]);

    $api->resource('suppliers', [
        'has-one' => [
            'user-history' => ['only' => ['read', 'related']],
        ],
    ]);
});
