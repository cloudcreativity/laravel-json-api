<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'web'], function () {
    Auth::routes();
});

JsonApi::register('v1', [], function (ApiGroup $api) {
    $api->resource('comments', [
        'controller' => true,
        'middleware' => 'auth',
        'has-one' => 'commentable',
    ]);
    $api->resource('countries', [
        'has-many' => ['users', 'posts'],
    ]);
    $api->resource('posts', [
        'controller' => true,
        'has-one' => [
            'author' => ['inverse' => 'users']
        ],
        'has-many' => ['comments', 'tags'],
    ]);
    $api->resource('users', [
        'has-one' => 'phone',
    ]);
    $api->resource('videos');
    $api->resource('tags', [
        'has-many' => 'taggables',
    ]);

    $api->resource('sites', [
        'controller' => true,
    ]);
});
