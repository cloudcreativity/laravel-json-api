<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;
use Illuminate\Foundation\Auth\User;
use CloudCreativity\LaravelJsonApi\Tests\Models;

/** @var EloquentFactory $factory */

/** Comment */
$factory->define(Models\Comment::class, function (Faker $faker) {
    return [
        'content' => $faker->paragraph,
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
    ];
});

$factory->state(Models\Comment::class, 'post', function () {
    return [
        'commentable_type' => Models\Post::class,
        'commentable_id' => function () {
            return factory(Models\Post::class)->create()->getKey();
        }
    ];
});

$factory->state(Models\Comment::class, 'video', function () {
    return [
        'commentable_type' => Models\Video::class,
        'commentable_id' => function () {
            return factory(Models\Video::class)->create()->getKey();
        }
    ];
});

/** Post */
$factory->define(Models\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'slug' => $faker->unique()->slug,
        'content' => $faker->text,
        'author_id' => function () {
            return factory(User::class)->create()->getKey();
        },
    ];
});

/** Tag */
$factory->define(Models\Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->country,
    ];
});

/** User */
$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->email,
        'password' => bcrypt(str_random(10)),
    ];
});

/** Video */
$factory->define(Models\Video::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph,
        'user_id' => function () {
            return factory(User::class)->create()->getKey();
        },
    ];
});
