<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use DummyApp\Http\Controllers\PostsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoutingTest extends TestCase
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
        $this->withRoutes(function () {
            JsonApi::register('default', [], function (ApiGroup $api) {
                $api->resource('posts', [
                    'has-one' => ['author'],
                    'has-many' => ['tags', 'comments'],
                ]);
            });
        });

        $expected = '\\' . PostsController::class . $action;
        $this->assertMatch($method, $url, $expected);
    }

    /**
     * @param $method
     * @param $url
     * @param $action
     * @dataProvider defaultsProvider
     */
    public function testCustomController($method, $url, $action)
    {
        $expected = '\Foo\Bar';

        $this->withRoutes(function () use ($expected) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($expected) {
                $api->resource('posts', [
                    'controller' => $expected,
                    'has-one' => 'author',
                    'has-many' => 'comments',
                ]);
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
        $this->withRoutes(function () use ($only) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($only) {
                $api->resource('posts', ['only' => $only]);
            });
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
        $this->withRoutes(function () use ($except) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($except) {
                $api->resource('posts', ['except' => $except]);
            });
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
        $this->withRoutes(function () use ($only) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($only) {
                $api->resource('posts', [
                    'has-one' => [
                        'author' => [
                            'only' => $only,
                        ],
                    ],
                ]);
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
        $this->withRoutes(function () use ($except) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($except) {
                $api->resource('posts', [
                    'has-one' => [
                        'author' => [
                            'except' => $except,
                        ],
                    ],
                ]);
            });
        });

        $this->assertRoutes($matches);
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
        $this->withRoutes(function () use ($only) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($only) {
                $api->resource('posts', [
                    'has-many' => [
                        'tags' => [
                            'only' => $only,
                        ],
                    ],
                ]);
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
        $this->withRoutes(function () use ($except) {
            JsonApi::register('default', [], function (ApiGroup $api) use ($except) {
                $api->resource('posts', [
                    'has-many' => [
                        'tags' => [
                            'except' => $except,
                        ],
                    ],
                ]);
            });
        });

        $this->assertRoutes($matches);
    }

    /**
     * @param $method
     * @param $url
     * @dataProvider recordProvider
     */
    public function testResourceIdConstraint($method, $url)
    {
        $this->withRoutes(function () {
            JsonApi::register('default', [], function (ApiGroup $api) {
                $api->resource('posts', [
                    'has-one' => ['author'],
                    'has-many' => ['tags', 'comments'],
                    'id' => '[A-Z]+',
                ]);
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
        $this->withRoutes(function () {
            JsonApi::register('default', ['id' => '[A-Z]+'], function (ApiGroup $api) {
                $api->resource('posts', [
                    'has-one' => ['author'],
                    'has-many' => ['tags', 'comments'],
                ]);
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
        $this->withRoutes(function () {
            JsonApi::register('default', ['id' => '[A-Z]+'], function (ApiGroup $api) {
                $api->resource('posts', [
                    'has-one' => ['author'],
                    'has-many' => ['tags', 'comments'],
                    'id' => null,
                ]);
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
        $this->withRoutes(function () {
            JsonApi::register('default', ['id' => '[0-9]+'], function (ApiGroup $api) {
                $api->resource('posts', [
                    'has-one' => ['author'],
                    'has-many' => ['tags', 'comments'],
                    'id' => '[A-Z]+',
                ]);
            });
        });

        $this->assertNotFound($method, $url);
    }

    /**
     * Wrap route definitions in the correct namespace.
     *
     * @param \Closure $closure
     */
    private function withRoutes(\Closure $closure)
    {
        Route::group([
            'namespace' => '\\DummyApp\\Http\\Controllers',
        ], $closure);
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
