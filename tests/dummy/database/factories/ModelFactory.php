<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/** @var EloquentFactory $factory */

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
            return factory(DummyApp\Post::class)->create()->getKey();
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
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});

/** Tag */
$factory->define(DummyApp\Tag::class, function (Faker $faker) {
    return [
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

/** Video */
$factory->define(DummyApp\Video::class, function (Faker $faker) {
    return [
        'uuid' => $faker->unique()->uuid,
        'title' => $faker->words(3, true),
        'description' => $faker->paragraph,
        'user_id' => function () {
            return factory(DummyApp\User::class)->create()->getKey();
        },
    ];
});
