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

use Closure;
use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\ServiceProvider;
use CloudCreativity\LaravelJsonApi\Testing\MakesJsonApiRequests;
use CloudCreativity\LaravelJsonApi\Tests\Entities;
use CloudCreativity\LaravelJsonApi\Tests\Exceptions\Handler;
use CloudCreativity\LaravelJsonApi\Tests\Models;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder as Schema;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * Class TestCase
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class TestCase extends BaseTestCase
{

    use MakesJsonApiRequests;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->setUpDatabase($this->app);
        $this->artisan('migrate');
        $this->app->singleton(Entities\SiteRepository::class);
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
            'JsonApi' => JsonApi::class,
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
            'comments' => Models\Comment::class,
            'countries' => Models\Country::class,
            'phones' => Models\Phone::class,
            'posts' => Models\Post::class,
            'sites' => Entities\Site::class,
            'tags' => Models\Tag::class,
            'users' => Models\User::class,
            'videos' => Models\Video::class,
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
        $this->modifyUsersTable($schema);
        $this->createPostsTable($schema);
        $this->createVideosTable($schema);
        $this->createCommentsTable($schema);
        $this->createTagsTables($schema);
        $this->createPhonesTable($schema);
        $this->createCountriesTable($schema);
        $this->createBlogsTable($schema);
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
            $table->unsignedInteger('author_id')->nullable();
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createVideosTable(Schema $schema)
    {
        $schema->create('videos', function (Blueprint $table) {
            $table->uuid('uuid');
            $table->timestamps();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('user_id');
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
            $table->nullableMorphs('commentable');
            $table->unsignedInteger('user_id');
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createTagsTables(Schema $schema)
    {
        $schema->create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
        });

        $schema->create('taggables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable');
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createPhonesTable(Schema $schema)
    {
        $schema->create('phones', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('number');
        });
    }

    /**
     * @param Schema $schema
     */
    protected function modifyUsersTable(Schema $schema)
    {
        $schema->table('users', function (Blueprint $table) {
            $table->unsignedInteger('country_id')->nullable();
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createCountriesTable(Schema $schema)
    {
        $schema->create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->string('code');
        });
    }

    /**
     * @param Schema $schema
     */
    protected function createBlogsTable(Schema $schema)
    {
        $schema->create('blogs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('title');
            $table->text('article');
            $table->timestamp('published_at');
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
     * Wrap route definitions in the correct namespace.
     *
     * @param Closure $closure
     */
    protected function withRoutes(Closure $closure)
    {
        Route::group([
            'namespace' => '\\CloudCreativity\\LaravelJsonApi\\Tests\\Http\\Controllers',
        ], $closure);
    }

    /**
     * @param Closure $closure
     * @param array $options
     */
    protected function withDefaultApi(array $options, Closure $closure)
    {
        $this->withRoutes(function () use ($options, $closure) {
            JsonApi::register('default', $options, $closure);
        });
    }
}
