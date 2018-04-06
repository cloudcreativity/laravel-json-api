<?php

use DummyPackage\Blog;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

/** @var EloquentFactory $factory */

/** Blog */
$factory->define(Blog::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'article' => $faker->text,
    ];
});

$factory->state(Blog::class, 'published', function (Faker $faker) {
    return [
        'published_at' => $faker->dateTimeBetween('-1 month', 'now'),
    ];
});
