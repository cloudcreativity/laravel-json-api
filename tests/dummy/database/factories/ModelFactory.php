<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/** @var EloquentFactory $factory */

/** Comment */
$factory->define(App\Comment::class, function (Faker $faker) {
    return [
        'content' => $faker->paragraph,
        'user_id' => function () {
            return factory(App\User::class)->create()->getKey();
        },
    ];
});

$factory->state(App\Comment::class, 'post', function () {
    return [
        'commentable_type' => App\Post::class,
        'commentable_id' => function () {
            return factory(App\Post::class)->create()->getKey();
        }
    ];
});

$factory->state(App\Comment::class, 'video', function () {
    return [
        'commentable_type' => App\Video::class,
        'commentable_id' => function () {
            return factory(App\Video::class)->create()->getKey();
        }
    ];
});

/** Country */
$factory->define(App\Country::class, function (Faker $faker) {
    return [
        'name' => $faker->country,
        'code' => $faker->countryCode,
    ];
});

/** Phone */
$factory->define(App\Phone::class, function (Faker $faker) {
    return [
        'number' => $faker->numerify('+447#########'),
    ];
});

$factory->state(App\Phone::class, 'user', function (Faker $faker) {
    return [
        'user_id' => function () {
            return factory(App\User::class)->create()->getKey();
        },
    ];
});

/** Post */
$factory->define(App\Post::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'slug' => $faker->unique()->slug,
        'content' => $faker->text,
        'author_id' => function () {
            return factory(App\User::class)->create()->getKey();
        },
    ];
});

/** Tag */
$factory->define(App\Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->country,
    ];
});

/** User */
$factory->define(App\User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->email,
        'password' => bcrypt(str_random(10)),
    ];
});

/** Video */
$factory->define(App\Video::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph,
        'user_id' => function () {
            return factory(App\User::class)->create()->getKey();
        },
    ];
});
