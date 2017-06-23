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

use CloudCreativity\LaravelJsonApi\Facade;
use CloudCreativity\LaravelJsonApi\ServiceProvider;
use CloudCreativity\LaravelJsonApi\Tests\Exceptions\Handler;
use CloudCreativity\LaravelJsonApi\Tests\Models;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Class TestCase
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class TestCase extends BaseTestCase
{

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
        $this->artisan('migrate');
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'JsonApi' => Facade::class,
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        /** @var Repository $config */
        $config = $app->make('config');
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $config->set('json-api-default', require __DIR__ . '/../../stubs/api.php');
        $config->set('json-api-default.namespace', '\\CloudCreativity\\LaravelJsonApi\\Tests\\JsonApi');
        $config->set('json-api-default.resources', [
            'posts' => Models\Post::class,
            'comments' => Models\Comment::class,
            'user' => User::class,
        ]);
    }

    /**
     * @param Application $app
     * @return Schema
     */
    protected function setUpDatabase(Application $app)
    {
        $this->loadLaravelMigrations('testbench');
        $schema = $app->make('db')->connection()->getSchemaBuilder();
        $this->createPostsTable($schema);
        $this->createCommentsTable($schema);
        $this->withFactories(__DIR__ . '/../factories');

        return $schema;
    }

    /**
     * @param Schema $schema
     */
    protected function createPostsTable(Schema $schema)
    {
        $schema->create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('title');
            $table->string('slug');
            $table->text('content');
            $table->unsignedInteger('author_id');
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createCommentsTable(Schema $schema)
    {
        $schema->create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->text('content');
            $table->unsignedInteger('post_id');
        });
    }

    /**
     * @param Application $app
     */
    protected function resolveApplicationExceptionHandler($app)
    {
        $app->singleton(ExceptionHandler::class, Handler::class);
    }

    /**
     * @param \Closure $closure
     */
    protected function withRoutes(\Closure $closure)
    {
        \Route::group([
            'namespace' => '\\CloudCreativity\\LaravelJsonApi\\Tests\\Http\\Controllers',
        ], $closure);
    }

    /**
     * @param \Closure $closure
     * @param array $options
     */
    protected function withDefaultApi(\Closure $closure, array $options = [])
    {
        $this->withRoutes(function () use ($closure, $options) {
            \JsonApi::api('default', $options, $closure);
        });
    }
}
