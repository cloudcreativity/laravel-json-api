<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use App;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\ServiceProvider;
use CloudCreativity\LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Package;

/**
 * Class TestCase
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class TestCase extends BaseTestCase
{

    use MakesJsonApiRequests;

    /**
     * Whether the dummy app routes should be used.
     *
     * @var bool
     */
    protected $appRoutes = true;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->artisan('migrate');

        if ($this->appRoutes) {
            $this->withAppRoutes();
        }
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            Package\ServiceProvider::class,
            App\Providers\AppServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'JsonApi' => JsonApi::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(ExceptionHandler::class, App\Exceptions\Handler::class);
    }

    /**
     * Use the default dummy app routes.
     *
     * @return $this
     */
    protected function withAppRoutes()
    {
        Route::group([
            'namespace' => 'App\\Http\\Controllers',
        ], function () {
            require __DIR__ . '/../../dummy/routes/json-api.php';
        });

        return $this;
    }

}
