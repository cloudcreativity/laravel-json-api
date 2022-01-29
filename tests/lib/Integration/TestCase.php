<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiRegistration;
use CloudCreativity\LaravelJsonApi\ServiceProvider;
use CloudCreativity\LaravelJsonApi\Testing\MakesJsonApiRequests;
use CloudCreativity\LaravelJsonApi\Testing\TestExceptionHandler;
use DummyApp;
use DummyApp\User;
use DummyPackage;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Laravel\Ui\UiServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Class TestCase
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class TestCase extends BaseTestCase
{

    use MakesJsonApiRequests;
    use InteractsWithDeprecationHandling;

    /**
     * Whether the dummy app routes should be used.
     *
     * @var bool
     */
    protected $appRoutes = true;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDeprecationHandling();

        config()->set('auth.guards.api', [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ]);

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
            UiServiceProvider::class,
            DummyPackage\ServiceProvider::class,
            DummyApp\Providers\AppServiceProvider::class,
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
        $app->singleton(ExceptionHandler::class, TestExceptionHandler::class);
    }

    /**
     * Use the default dummy app routes.
     *
     * @return $this
     * @deprecated use acceptance tests to test the dummy app.
     */
    protected function withAppRoutes()
    {
        Route::middleware('web')
            ->namespace($namespace = 'DummyApp\Http\Controllers')
            ->group(__DIR__ . '/../../dummy/routes/web.php');

        Route::group(compact('namespace'), function () {
            require __DIR__ . '/../../dummy/routes/api.php';
        });

        $this->refreshRoutes();

        return $this;
    }

    /**
     * @param \Closure $callback
     * @param array $options
     * @param string $api
     * @return $this
     */
    protected function withRoutes(\Closure $callback, array $options = [], string $api = 'v1')
    {
        Route::group([
            'namespace' => 'DummyApp\Http\Controllers',
        ], function () use ($api, $options, $callback) {

            if (empty($options)) {
                JsonApi::register($api, $callback);
            } else {
                JsonApi::register($api, $options, $callback);
            }
        });

        return $this;
    }

    /**
     * @param string $api
     * @return ApiRegistration
     */
    protected function withFluentRoutes(string $api = 'v1'): ApiRegistration
    {
        return JsonApi::register($api)->withNamespace('DummyApp\Http\Controllers');
    }

    /**
     * Refresh the router.
     *
     * This is required because it is the same as what a Laravel application's
     * route service provider does. Without it, some of the names and actions
     * may be missing from the maps within the router's route collection.
     *
     * @return $this
     * @see https://github.com/laravel/framework/issues/19020#issuecomment-409873471
     */
    protected function refreshRoutes()
    {
        /** @var Router $router */
        $router = app('router');

        $router->getRoutes()->refreshNameLookups();
        $router->getRoutes()->refreshActionLookups();

        return $this;
    }

    /**
     * @param string ...$states
     * @return $this
     */
    protected function actingAsUser(...$states)
    {
        $this->actingAs(factory(User::class)->states($states)->create(), 'api');

        return $this;
    }

}
