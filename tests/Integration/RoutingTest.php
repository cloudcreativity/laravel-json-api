<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Http\Controllers\PostsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoutingTest extends TestCase
{

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

    public function testHasOne()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testHasOneOnly()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testHasOneExcept()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testHasMany()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testHasManyOnly()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testHasManyExcept()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testResourceIdConstraint()
    {
        $this->markTestIncomplete('@todo');
    }

    public function testDefaultIdConstraint()
    {
        $this->markTestIncomplete('@todo');
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
