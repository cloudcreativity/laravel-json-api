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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Routing;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Routing\RelationshipsRegistration;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Test extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var array
     */
    private $defaults = [
        'index' => ['GET', '/api/v1/posts', '@index'],
        'create' => ['POST', '/api/v1/posts', '@create'],
        'read' => ['GET', '/api/v1/posts/1', '@read'],
        'update' => ['PATCH', '/api/v1/posts/1', '@update'],
        'delete' => ['DELETE', '/api/v1/posts/1', '@delete'],
        'has-one related' => ['GET', '/api/v1/posts/1/author', '@readRelatedResource'],
        'has-one read' => ['GET', '/api/v1/posts/1/relationships/author', '@readRelationship'],
        'has-one replace' => ['PATCH', '/api/v1/posts/1/relationships/author', '@replaceRelationship'],
        'has-many related' => ['GET', '/api/v1/posts/1/comments', '@readRelatedResource'],
        'has-many read' => ['GET', '/api/v1/posts/1/relationships/comments', '@readRelationship'],
        'has-many replace' => ['PATCH', '/api/v1/posts/1/relationships/comments', '@replaceRelationship'],
        'has-many add' => ['POST', '/api/v1/posts/1/relationships/comments', '@addToRelationship'],
        'has-many remove' => ['DELETE', '/api/v1/posts/1/relationships/comments', '@removeFromRelationship'],
    ];

    /**
     * @return array
     */
    public function defaultsProvider()
    {
        return $this->defaults;
    }

    /**
     * Provider of all routes that relate to a specific record, i.e. have an id in them.
     *
     * @return array
     */
    public function recordProvider()
    {
        $args = $this->defaults;
        unset($args['index'], $args['create']);

        return $args;
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testDefaults($method, $url, $action)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => ['author'],
                'has-many' => ['tags', 'comments'],
            ]);
        });

        $this->assertMatch($method, $url, '\\' . JsonApiController::class . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testFluentDefaults($method, $url, $action)
    {
        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertMatch($method, $url, '\\' . JsonApiController::class . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testControllerIsTrue($method, $url, $action)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'controller' => true,
                'has-one' => 'author',
                'has-many' => 'comments',
            ]);
        });

        $expected = 'DummyApp\Http\Controllers\PostsController';

        $this->assertMatch($method, $url, $expected . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testFluentControllerIsTrue($method, $url, $action)
    {
        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->controller()->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $expected = 'DummyApp\Http\Controllers\PostsController';

        $this->assertMatch($method, $url, $expected . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testFluentControllerIsTrueAndSingular($method, $url, $action)
    {
        $this->withFluentRoutes()->singularControllers()->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->controller()->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $expected = 'DummyApp\Http\Controllers\PostController';

        $this->assertMatch($method, $url, $expected . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testControllerIsString($method, $url, $action)
    {
        $expected = '\Foo\Bar';

        $this->withRoutes(function (RouteRegistrar $api) use ($expected) {
            $api->resource('posts', [
                'controller' => $expected,
                'has-one' => 'author',
                'has-many' => 'comments',
            ]);
        });

        $this->assertMatch($method, $url, $expected . $action);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testFluentControllerIsString($method, $url, $action)
    {
        $expected = '\Foo\Bar';

        $this->withRoutes(function (RouteRegistrar $api) use ($expected) {
            $api->resource('posts')->controller($expected)->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertMatch($method, $url, $expected . $action);
    }

    /**
     * @return array
     */
    public function onlyProvider()
    {
        return [
            ['index', [
                ['GET', '/api/v1/posts', 200],
                ['POST', '/api/v1/posts', 405],
                ['GET', '/api/v1/posts/1', 404],
                ['PATCH', '/api/v1/posts/1', 404],
                ['DELETE', '/api/v1/posts/1', 404],
            ]],
            [['index', 'read'], [
                ['GET', '/api/v1/posts', 200],
                ['POST', '/api/v1/posts', 405],
                ['GET', '/api/v1/posts/1', 200],
                ['PATCH', '/api/v1/posts/1', 405],
                ['DELETE', '/api/v1/posts/1', 405],
            ]],
            [['create', 'read', 'update', 'delete'], [
                ['GET', '/api/v1/posts', 405],
                ['POST', '/api/v1/posts', 200],
                ['GET', '/api/v1/posts/1', 200],
                ['PATCH', '/api/v1/posts/1', 200],
                ['DELETE', '/api/v1/posts/1', 200],
            ]],
        ];
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider onlyProvider
     */
    public function testOnly($only, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts', ['only' => $only]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider onlyProvider
     */
    public function testFluentOnly($only, array $matches)
    {
        $only = Arr::wrap($only);

        $this->withRoutes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts')->only(...$only);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @return array
     */
    public function exceptProvider()
    {
        return [
            ['create', [
                ['GET', '/api/v1/posts', 200],
                ['POST', '/api/v1/posts', 405],
                ['GET', '/api/v1/posts/1', 200],
                ['PATCH', '/api/v1/posts/1', 200],
                ['DELETE', '/api/v1/posts/1', 200],
            ]],
            [['update', 'delete'], [
                ['GET', '/api/v1/posts', 200],
                ['POST', '/api/v1/posts', 200],
                ['GET', '/api/v1/posts/1', 200],
                ['PATCH', '/api/v1/posts/1', 405],
                ['DELETE', '/api/v1/posts/1', 405],
            ]],
            [['index', 'create'], [
                ['GET', '/api/v1/posts', 404],
                ['POST', '/api/v1/posts', 404],
                ['GET', '/api/v1/posts/1', 200],
                ['PATCH', '/api/v1/posts/1', 200],
                ['DELETE', '/api/v1/posts/1', 200],
            ]],
        ];
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider exceptProvider
     */
    public function testExcept($except, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts', ['except' => $except]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider exceptProvider
     */
    public function testFluentExcept($except, array $matches)
    {
        $except = Arr::wrap($except);

        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts')->except(...$except);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @return array
     */
    public function hasOneOnlyProvider()
    {
        return [
            ['related', [
                ['GET', '/api/v1/posts/1/author', 200],
                ['GET', '/api/v1/posts/1/relationships/author', 404],
                ['PATCH', '/api/v1/posts/1/relationships/author', 404],
            ]],
            [['related', 'read'], [
                ['GET', '/api/v1/posts/1/author', 200],
                ['GET', '/api/v1/posts/1/relationships/author', 200],
                ['PATCH', '/api/v1/posts/1/relationships/author', 405],
            ]],
            ['replace', [
                ['GET', '/api/v1/posts/1/author', 404],
                ['GET', '/api/v1/posts/1/relationships/author', 405],
                ['PATCH', '/api/v1/posts/1/relationships/author', 200],
            ]],
        ];
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider hasOneOnlyProvider
     */
    public function testHasOneOnly($only, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts', [
                'has-one' => [
                    'author' => [
                        'only' => $only,
                    ],
                ],
            ]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider hasOneOnlyProvider
     */
    public function testFluentHasOneOnly($only, array $matches)
    {
        $only = Arr::wrap($only);

        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) use ($only) {
                $rel->hasOne('author')->only(...$only);
            });
        });

        $this->assertRoutes($matches);
    }

    /**
     * @return array
     */
    public function hasOneExceptProvider()
    {
        return [
            ['related', [
                ['GET', '/api/v1/posts/1/author', 404],
                ['GET', '/api/v1/posts/1/relationships/author', 200],
                ['PATCH', '/api/v1/posts/1/relationships/author', 200],
            ]],
            [['related', 'read'], [
                ['GET', '/api/v1/posts/1/author', 404],
                ['GET', '/api/v1/posts/1/relationships/author', 405],
                ['PATCH', '/api/v1/posts/1/relationships/author', 200],
            ]],
            ['replace', [
                ['GET', '/api/v1/posts/1/author', 200],
                ['GET', '/api/v1/posts/1/relationships/author', 200],
                ['PATCH', '/api/v1/posts/1/relationships/author', 405],
            ]],
        ];
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider hasOneExceptProvider
     */
    public function testHasOneExcept($except, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts', [
                'has-one' => [
                    'author' => [
                        'except' => $except,
                    ],
                ],
            ]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider hasOneExceptProvider
     */
    public function testFluentHasOneExcept($except, array $matches)
    {
        $except = Arr::wrap($except);

        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) use ($except) {
                $rel->hasOne('author')->except(...$except);
            });
        });

        $this->assertRoutes($matches);
    }

    public function testHasOneInverse(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => [
                    'author' => [
                        'inverse' => 'users',
                    ],
                ],
            ]);
        });

        $route = $this->assertMatch('GET', '/api/v1/posts/1/author');
        $this->assertEquals([
            ResourceRegistrar::PARAM_RESOURCE_TYPE => 'posts',
            ResourceRegistrar::PARAM_RELATIONSHIP_NAME => 'author',
            ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE => 'users',
        ], $route->defaults);
    }

    public function testFluentHasOneInverse(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author', 'users');
            });
        });

        $route = $this->assertMatch('GET', '/api/v1/posts/1/author');
        $this->assertEquals([
            ResourceRegistrar::PARAM_RESOURCE_TYPE => 'posts',
            ResourceRegistrar::PARAM_RELATIONSHIP_NAME => 'author',
            ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE => 'users',
        ], $route->defaults);
    }

    /**
     * @return array
     */
    public function hasManyOnlyProvider()
    {
        return [
            ['related', [
                ['GET', '/api/v1/posts/1/tags', 200],
                ['GET', '/api/v1/posts/1/relationships/tags', 404],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 404],
                ['POST', '/api/v1/posts/1/relationships/tags', 404],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 404],
            ]],
            [['related', 'read'], [
                ['GET', '/api/v1/posts/1/tags', 200],
                ['GET', '/api/v1/posts/1/relationships/tags', 200],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 405],
                ['POST', '/api/v1/posts/1/relationships/tags', 405],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 405],
            ]],
            ['replace', [
                ['GET', '/api/v1/posts/1/tags', 404],
                ['GET', '/api/v1/posts/1/relationships/tags', 405],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 200],
                ['POST', '/api/v1/posts/1/relationships/tags', 405],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 405],
            ]],
            [['add', 'remove'], [
                ['GET', '/api/v1/posts/1/tags', 404],
                ['GET', '/api/v1/posts/1/relationships/tags', 405],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 405],
                ['POST', '/api/v1/posts/1/relationships/tags', 200],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 200],
            ]],
        ];
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider hasManyOnlyProvider
     */
    public function testHasManyOnly($only, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts', [
                'has-many' => [
                    'tags' => [
                        'only' => $only,
                    ],
                ],
            ]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $only
     * @param array $matches
     * @dataProvider hasManyOnlyProvider
     */
    public function testFluentHasManyOnly($only, array $matches)
    {
        $only = Arr::wrap($only);

        $this->withRoutes(function (RouteRegistrar $api) use ($only) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) use ($only) {
                $rel->hasMany('tags')->only(...$only);
            });
        });

        $this->assertRoutes($matches);
    }

    /**
     * @return array
     */
    public function hasManyExceptProvider()
    {
        return [
            ['related', [
                ['GET', '/api/v1/posts/1/tags', 404],
                ['GET', '/api/v1/posts/1/relationships/tags', 200],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 200],
                ['POST', '/api/v1/posts/1/relationships/tags', 200],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 200],
            ]],
            [['related', 'read'], [
                ['GET', '/api/v1/posts/1/tags', 404],
                ['GET', '/api/v1/posts/1/relationships/tags', 405],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 200],
                ['POST', '/api/v1/posts/1/relationships/tags', 200],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 200],
            ]],
            ['replace', [
                ['GET', '/api/v1/posts/1/tags', 200],
                ['GET', '/api/v1/posts/1/relationships/tags', 200],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 405],
                ['POST', '/api/v1/posts/1/relationships/tags', 200],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 200],
            ]],
            [['add', 'remove'], [
                ['GET', '/api/v1/posts/1/tags', 200],
                ['GET', '/api/v1/posts/1/relationships/tags', 200],
                ['PATCH', '/api/v1/posts/1/relationships/tags', 200],
                ['POST', '/api/v1/posts/1/relationships/tags', 405],
                ['DELETE', '/api/v1/posts/1/relationships/tags', 405],
            ]],
        ];
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider hasManyExceptProvider
     */
    public function testHasManyExcept($except, array $matches)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts', [
                'has-many' => [
                    'tags' => [
                        'except' => $except,
                    ],
                ],
            ]);
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $except
     * @param array $matches
     * @dataProvider hasManyExceptProvider
     */
    public function testFluentHasManyExcept($except, array $matches)
    {
        $except = Arr::wrap($except);

        $this->withRoutes(function (RouteRegistrar $api) use ($except) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) use ($except) {
                $rel->hasMany('tags')->except(...$except);
            });
        });

        $this->assertRoutes($matches);
    }


    public function testHasManyInverse(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-many' => [
                    'tags' => [
                        'inverse' => 'topics',
                    ],
                ],
            ]);
        });

        $route = $this->assertMatch('GET', '/api/v1/posts/1/tags');
        $this->assertEquals([
            ResourceRegistrar::PARAM_RESOURCE_TYPE => 'posts',
            ResourceRegistrar::PARAM_RELATIONSHIP_NAME => 'tags',
            ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE => 'topics',
        ], $route->defaults);
    }

    public function testFluentHasManyInverse(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasMany('tags', 'topics');
            });
        });

        $route = $this->assertMatch('GET', '/api/v1/posts/1/tags');
        $this->assertEquals([
            ResourceRegistrar::PARAM_RESOURCE_TYPE => 'posts',
            ResourceRegistrar::PARAM_RELATIONSHIP_NAME => 'tags',
            ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE => 'topics',
        ], $route->defaults);
    }

    /**
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testResourceIdConstraint($method, $url)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => ['author'],
                'has-many' => ['tags', 'comments'],
                'id' => '^[A-Z]+$',
            ]);
        });

        $this->assertNotFound($method, $url);
    }

    /**
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testFluentResourceIdConstraint($method, $url)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts')->id('^[A-Z]+$')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertNotFound($method, $url);
    }

    /**
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testDefaultIdConstraint($method, $url)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => ['author'],
                'has-many' => ['tags', 'comments'],
            ]);
        }, ['id' => '^[A-Z]+$']);

        $this->assertNotFound($method, $url);
    }

    /**
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testFluentDefaultIdConstraint($method, $url)
    {
        $this->withFluentRoutes()->defaultId('^[A-Z]+$')->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertNotFound($method, $url);
    }

    /**
     * If there is a default ID constraint, it can be removed using `null` on a resource.
     *
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testDefaultIdConstraintCanBeIgnoredByResource($method, $url)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => ['author'],
                'has-many' => ['tags', 'comments'],
                'id' => null,
            ]);
        }, ['id' => '^[A-Z]+$']);

        $this->assertMatch($method, $url);
    }

    /**
     * If there is a default ID constraint, it can be removed using `null` on a resource.
     *
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testFluentDefaultIdConstraintCanBeIgnoredByResource($method, $url)
    {
        $this->withFluentRoutes()->defaultId('^[A-Z]+$')->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->id(null)->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertMatch($method, $url);
    }

    /**
     * If there is a default and a resource ID constraint, the resource ID constraint is used.
     *
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testResourceIdConstraintOverridesDefaultIdConstraint($method, $url)
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'has-one' => ['author'],
                'has-many' => ['tags', 'comments'],
                'id' => '^[A-Z]+$',
            ]);
        }, ['id' => '^[0-9]+$']);

        $this->assertNotFound($method, $url);
    }

    /**
     * If there is a default and a resource ID constraint, the resource ID constraint is used.
     *
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testFluentResourceIdConstraintOverridesDefaultIdConstraint($method, $url)
    {
        $this->withFluentRoutes()->defaultId('^[0-9]+$')->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->id('^[A-Z]+$')->relationships(function (RelationshipsRegistration $rel) {
                $rel->hasOne('author');
                $rel->hasMany('tags');
                $rel->hasMany('comments');
            });
        });

        $this->assertNotFound($method, $url);
    }

    /**
     * @return array
     */
    public function multiWordProvider()
    {
        return [
            ['end-users'],
            ['end_users'],
            ['endUsers'],
        ];
    }

    /**
     * @param $resourceType
     * @dataProvider multiWordProvider
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/224
     */
    public function testMultiWordResourceType($resourceType)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($resourceType) {
            $api->resource($resourceType, [
                'has-one' => ['author'],
                'has-many' => ['tags'],
            ]);
        });

        $base = "/api/v1/$resourceType";

        $this->assertMatch('GET', $base, '\\' . JsonApiController::class . '@index');
    }

    /**
     * @param $relationship
     * @dataProvider multiWordProvider
     */
    public function testMultiWordRelationship($relationship)
    {
        $this->withRoutes(function (RouteRegistrar $api) use ($relationship) {
            $api->resource('posts', [
                'has-many' => $relationship,
            ]);
        });

        $self = "/api/v1/posts/1/relationships/{$relationship}";
        $related = "/api/v1/posts/1/{$relationship}";

        $this->assertMatch('GET', $self, '\\' . JsonApiController::class . '@readRelationship');
        $this->assertMatch('GET', $related, '\\' . JsonApiController::class . '@readRelatedResource');
    }

    /**
     * @return array
     */
    public function processProvider(): array
    {
        return [
            'fetch-many' => ['GET', '/api/v1/photos/queue-jobs', '@processes'],
            'fetch-one' => ['GET', '/api/v1/photos/queue-jobs/839765f4-7ff4-4625-8bf7-eecd3ab44946', '@process'],
        ];
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $action
     * @dataProvider processProvider
     */
    public function testAsync(string $method, string $url, string $action): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('photos', [
                'async' => true,
            ]);
        }, ['id' => '^\d+$']);

        $this->assertMatch($method, $url, '\\' . JsonApiController::class . $action);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string $action
     * @dataProvider processProvider
     */
    public function testFluentAsync(string $method, string $url, string $action): void
    {
        $this->withFluentRoutes()->defaultId('^\d+$')->routes(function (RouteRegistrar $api) {
            $api->resource('photos')->async();
        });

        $this->assertMatch($method, $url, '\\' . JsonApiController::class . $action);
    }

    /**
     * Test that the default async job id constraint is a UUID.
     */
    public function testAsyncDefaultConstraint(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('photos', [
                'async' => true,
            ]);
        });

        $this->assertNotFound('GET', '/api/v1/photos/queue-jobs/123456');
    }

    /**
     * Test that the default async job id constraint is a UUID.
     */
    public function testAsyncCustomConstraint(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('photos', [
                'async' => true,
                'async_id' => '^\d+$',
            ]);
        });

        $this->assertMatch('GET', '/api/v1/photos/queue-jobs/123456');
    }

    /**
     * Test that the default async job id constraint is a UUID.
     */
    public function testFluentAsyncCustomConstraint(): void
    {
        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('photos')->async('^\d+$');
        });

        $this->assertMatch('GET', '/api/v1/photos/queue-jobs/123456');
    }

    /**
     * @param $method
     * @param $url
     * @param int $expected
     */
    private function assertRoute($method, $url, $expected = 200)
    {
        if (405 === $expected) {
            $this->assertMethodNotAllowed($method, $url);
        } elseif (404 === $expected) {
            $this->assertNotFound($method, $url);
        } else {
            $this->assertMatch($method, $url);
        }
    }

    /**
     * @param array $routes
     */
    private function assertRoutes(array $routes)
    {
        foreach ($routes as list($method, $url, $expected)) {
            $this->assertRoute($method, $url, $expected);
        }
    }

    /**
     * @param $method
     * @param $url
     * @param $expected
     * @return \Illuminate\Routing\Route
     */
    private function assertMatch($method, $url, $expected = null)
    {
        $request = $this->createRequest($method, $url);
        $route = null;

        try {
            $route = Route::getRoutes()->match($request);
            $matched = true;
        } catch (NotFoundHttpException $e) {
            $matched = false;
        } catch (MethodNotAllowedHttpException $e) {
            $matched = false;
        }

        $this->assertTrue($matched, "Route $method $url did not match.");

        if ($expected) {
            $this->assertSame($expected, $route->action['controller']);
        }

        return $route;
    }

    /**
     * @param $method
     * @param $url
     */
    private function assertMethodNotAllowed($method, $url)
    {
        $request = $this->createRequest($method, $url);
        $notAllowed = false;

        try {
            Route::getRoutes()->match($request);
        } catch (MethodNotAllowedHttpException $e) {
            $notAllowed = true;
        }

        $this->assertTrue($notAllowed, "Route $method $url is allowed");
    }

    /**
     * @param $method
     * @param $url
     */
    private function assertNotFound($method, $url)
    {
        $request = $this->createRequest($method, $url);
        $notFound = false;

        try {
            Route::getRoutes()->match($request);
        } catch (NotFoundHttpException $e) {
            $notFound = true;
        }

        $this->assertTrue($notFound, "Route $method $url is found");
    }

    /**
     * @param $method
     * @param $url
     * @return Request
     */
    private function createRequest($method, $url)
    {
        return Request::create($url, $method);
    }
}
