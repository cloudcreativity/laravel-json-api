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

namespace DummyApp\Providers;

use DummyApp\Entities\SiteRepository;
use DummyApp\Policies\PostPolicy;
use DummyApp\Post;
use Illuminate\Database\Eloquent\Factory as ModelFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class DummyServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
        config()->set('database.default', 'testbench');
        config()->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('json-api-default', require __DIR__ . '/../../config/json-api-default.php');

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->app->make(ModelFactory::class)->load(__DIR__ . '/../../database/factories');

        Gate::policy(Post::class, PostPolicy::class);
    }

    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SiteRepository::class);
    }

}
