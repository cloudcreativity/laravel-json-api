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

use CloudCreativity\LaravelJsonApi\Queue\ClientJob;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */

$factory->define(ClientJob::class, function (Faker $faker) {
    return [
        'api' => 'v1',
        'failed' => false,
        'resource_type' => 'downloads',
        'attempts' => 0,
    ];
});

$factory->state(ClientJob::class, 'success', function (Faker $faker) {
    return [
        'completed_at' => $faker->dateTimeBetween('-10 minutes', 'now'),
        'failed' => false,
        'attempts' => $faker->numberBetween(1, 3),
    ];
});
