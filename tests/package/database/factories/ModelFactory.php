<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

use DummyPackage\Blog;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/** @var EloquentFactory $factory */

/** Blog */
$factory->define(Blog::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(),
        'article' => $faker->text(),
    ];
});

$factory->state(Blog::class, 'published', function (Faker $faker) {
    return [
        'published_at' => $faker->dateTimeBetween('-1 month', 'now'),
    ];
});
