<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/** @var EloquentFactory $factory */

/** Avatar */
$factory->define(DummyApp\Avatar::class, function (Faker $faker) {
    return [
        'path' => 'avatars/' . str_random(6) . '.jpg',
        'media_type' => 'image/jpeg',
        'user_id' => function () {
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});

/** Comment */
$factory->define(DummyApp\Comment::class, function (Faker $faker) {
    return [
        'content' => $faker->paragraph,
        'user_id' => function () {
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});

$factory->state(DummyApp\Comment::class, 'post', function () {
    return [
        'commentable_type' => DummyApp\Post::class,
        'commentable_id' => function () {
            return factory(DummyApp\Post::class)->states('published')->create()->getKey();
        }
    ];
});

$factory->state(DummyApp\Comment::class, 'video', function () {
    return [
        'commentable_type' => DummyApp\Video::class,
        'commentable_id' => function () {
            return factory(DummyApp\Video::class)->create()->getKey();
        }
    ];
});

/** Country */
$factory->define(DummyApp\Country::class, function (Faker $faker) {
    return [
        'name' => $faker->country,
        'code' => $faker->countryCode,
    ];
});

/** Download */
$factory->define(DummyApp\Download::class, function (Faker $faker) {
    return [
        'category' => $faker->randomElement(['my-posts', 'my-comments', 'my-videos']),
    ];
});

/** Phone */
$factory->define(DummyApp\Phone::class, function (Faker $faker) {
    return [
        'number' => $faker->numerify('+447#########'),
    ];
});

$factory->state(DummyApp\Phone::class, 'user', function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});

/** Post */
$factory->define(DummyApp\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'slug' => $faker->unique()->slug,
        'content' => $faker->text,
        'author_id' => function () {
            return factory(DummyApp\User::class)->states('author')->create()->getKey();
        },
    ];
});

$factory->state(DummyApp\Post::class, 'published', function (Faker $faker) {
    return [
        'published_at' => $faker->dateTimeBetween('-1 month', 'now'),
    ];
});

/** Tag */
$factory->define(DummyApp\Tag::class, function (Faker $faker) {
    return [
        'uuid' => $faker->uuid,
        'name' => $faker->country,
    ];
});

/** User */
$factory->define(DummyApp\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->email,
        'password' => bcrypt(str_random(10)),
    ];
});

$factory->state(DummyApp\User::class, 'author', function () {
    return ['author' => true];
});

$factory->state(DummyApp\User::class, 'admin', function () {
    return ['admin' => true];
});

/** Video */
$factory->define(DummyApp\Video::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'url' => $faker->url,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph,
        'user_id' => function () {
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});
