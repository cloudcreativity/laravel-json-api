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

namespace CloudCreativity\LaravelJsonApi\Routing;

use App\Http\Controllers\PostsController;
use CloudCreativity\LaravelJsonApi\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Class ResourceRegistrarTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceRegistrarTest extends TestCase
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    protected function setUp()
    {
        /** @var Dispatcher $events */
        $events = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->router = new Router($events);
        $this->registrar = new ResourceRegistrar($this->router);
    }

    public function testResources()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts');
            $this->registrar->resource('comments');
        });

        $this->assertResource('posts', '1');
        $this->assertResource('comments', '2');

    }

    public function testNotAResource()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts');
            $this->registrar->resource('comments');
        });

        $this->assertNotResource('tags', '1');
    }

    public function testRelationships()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
            $this->registrar->resource('comments', [
                'has-one' => ['user', 'post'],
                'has-many' => 'likes',
            ]);
        });

        $this->assertResource('posts', '1');
        $this->assertHasOne('posts', '1', 'author');
        $this->assertHasMany('posts', '1', 'comments');
        $this->assertHasMany('posts', '1', 'tags');
        $this->assertResource('comments', '2');
        $this->assertHasOne('comments', '2', 'user');
        $this->assertHasOne('comments', '2', 'post');
        $this->assertHasMany('comments', '2', 'likes');
    }

    public function testDasherizedRelationships()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'has-one' => ['last-comment'],
                'has-many' => ['recent-comments'],
            ]);
        });

        $this->assertHasOne('posts', '1', 'last-comment');
        $this->assertHasMany('posts', '1', 'recent-comments');
    }

    public function testNotARelationship()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
        });

        $this->assertNotRelationship('posts', '1', 'site');
    }

    public function testSpecifiedController()
    {
        $this->registrar->resource('posts', [
            'controller' => PostsController::class,
            'has-one' => 'author',
            'has-many' => 'comments',
        ]);

        $this->assertResource('posts', '1');
        $this->assertHasOne('posts', '1', 'author');
        $this->assertHasMany('posts', '1', 'comments');
    }

    public function testSpecifiedAuthorizer()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'authorizer' => 'App\JsonApi\GenericAuthorizer',
            ]);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertAuthorizer($route, 'App\JsonApi\GenericAuthorizer');
    }

    public function testSpecifiedValidators()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'validators' => 'App\JsonApi\GenericValidator',
            ]);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertValidator($route, 'App\JsonApi\GenericValidator');
    }

    public function testWithIdConstraint()
    {
        $this->router->group(['namespace' => 'App\Http\Controllers'], function () {
            $this->registrar->resource('posts', [
                'has-one' => 'author',
                'has-many' => 'comments',
                'id' => '[9]+',
            ]);
        });

        $this->assertResource('posts', '99');
        $this->assertHasOne('posts', '99', 'author');
        $this->assertHasMany('posts', '99', 'comments');

        $this->assertNotRead('posts', '1');
        $this->assertNotUpdate('posts', '1');
        $this->assertNotDelete('posts', '1');
        $this->assertNotRelationship('posts', '1', 'author');
        $this->assertNotRelationship('posts', '1', 'comments');
    }

    /**
     * @param Request $request
     * @param $name
     * @return Route
     */
    private function matchRoute(Request $request, $name)
    {
        try {
            $route = $this->router->getRoutes()->match($request);
            $route->bind($request);
            $this->assertSame($name, $route->getName(), 'route name');
            return $route;
        } catch (NotFoundHttpException $e) {
            $this->fail("Route $name not found.");
        } catch (MethodNotAllowedException $e) {
            $this->fail("Route $name not allowed.");
        }
    }

    /**
     * @param Request $request
     * @param string $resourceType
     * @param string $content
     * @param string $name
     * @param string|null $relationship
     * @return Route
     */
    private function assertRoute(Request $request, $resourceType, $content, $name, $relationship = null)
    {
        $route = $this->matchRoute($request, $name);

        $this->assertEquals($content, $route->run());
        $this->assertEquals($resourceType, $route->parameter('resource_type'), 'resource type');
        $this->assertEquals($relationship, $route->parameter('relationship_name'), 'relationship name');

        return $route;
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @return void
     */
    private function assertResource($resourceType, $id)
    {
        $this->assertIndex($resourceType);
        $this->assertCreate($resourceType);
        $this->assertRead($resourceType, $id);
        $this->assertUpdate($resourceType, $id);
        $this->assertDelete($resourceType, $id);
    }

    /**
     * @param $resourceType
     * @param $id
     */
    private function assertNotResource($resourceType, $id)
    {
        $this->assertNotIndex($resourceType);
        $this->assertNotCreate($resourceType);
        $this->assertNotRead($resourceType, $id);
        $this->assertNotUpdate($resourceType, $id);
        $this->assertNotDelete($resourceType, $id);
    }

    /**
     * @param string $resourceType
     * @return Route
     */
    private function assertIndex($resourceType)
    {
        return $this->assertRoute(
            Request::create("/{$resourceType}"),
            $resourceType,
            "{$resourceType}:index",
            "{$resourceType}.index"
        );
    }

    /**
     * @param string $resourceType
     * @param bool $notFound
     */
    private function assertNotIndex($resourceType, $notFound = true)
    {
        $this->assertNotRoute(Request::create("/{$resourceType}"), $notFound, "index {$resourceType}");
    }

    /**
     * @param string $resourceType
     * @return Route
     */
    private function assertCreate($resourceType)
    {
        return $this->assertRoute(
            Request::create("/{$resourceType}", 'POST'),
            $resourceType,
            "{$resourceType}:create",
            "{$resourceType}.create"
        );
    }

    /**
     * @param string $resourceType
     * @param bool $notFound
     */
    private function assertNotCreate($resourceType, $notFound = true)
    {
        $this->assertNotRoute(Request::create("/{$resourceType}"), $notFound,"create {$resourceType}");
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @return Route
     */
    private function assertRead($resourceType, $id)
    {
        return $this->assertRoute(
            Request::create("/{$resourceType}/{$id}"),
            $resourceType,
            "{$resourceType}:read:{$id}",
            "{$resourceType}.read"
        );
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @param bool $notFound
     */
    private function assertNotRead($resourceType, $id, $notFound = true)
    {
        $this->assertNotRoute(Request::create("/{$resourceType}/{$id}"), $notFound, "read {$resourceType}:{$id}");
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @return Route
     */
    private function assertUpdate($resourceType, $id)
    {
        return $this->assertRoute(
            Request::create("/{$resourceType}/{$id}", 'PATCH'),
            $resourceType,
            "{$resourceType}:update:{$id}",
            "{$resourceType}.update"
        );
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @param bool $notFound
     */
    private function assertNotUpdate($resourceType, $id, $notFound = true)
    {
        $this->assertNotRoute(Request::create("/{$resourceType}/{$id}", 'PATCH'), $notFound, "update {$resourceType}:{$id}");
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @return Route
     */
    private function assertDelete($resourceType, $id)
    {
        return $this->assertRoute(
            Request::create("/{$resourceType}/{$id}", 'DELETE'),
            $resourceType,
            "{$resourceType}:delete:{$id}",
            "{$resourceType}.delete"
        );
    }

    /**
     * @param string $resourceType
     * @param string $id
     * @param bool $notFound
     */
    private function assertNotDelete($resourceType, $id, $notFound = true)
    {
        $this->assertNotRoute(Request::create("/{$resourceType}/{$id}", 'DELETE'), $notFound, "delete {$resourceType}:{$id}");
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertRelated($resourceType, $id, $relationship)
    {
        $this->assertRoute(
            Request::create("/$resourceType/$id/$relationship"),
            $resourceType,
            "{$resourceType}:read-related:$id",
            "{$resourceType}.relationships.$relationship",
            $relationship
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     * @param bool $notFound
     */
    private function assertNotRelated($resourceType, $id, $relationship, $notFound = true)
    {
        $this->assertNotRoute(
            Request::create("/$resourceType/$id/$relationship"),
            $notFound,
            "read related {$resourceType}:{$id}:{$relationship}"
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertReadRelationship($resourceType, $id, $relationship)
    {
        $this->assertRoute(
            Request::create("/$resourceType/$id/relationships/$relationship"),
            $resourceType,
            "{$resourceType}:read-relationship:$id",
            "{$resourceType}.relationships.$relationship.read",
            $relationship
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     * @param bool $notFound
     */
    private function assertNotReadRelationship($resourceType, $id, $relationship, $notFound = true)
    {
        $this->assertNotRoute(
            Request::create("/$resourceType/$id/relationships/$relationship"),
            $notFound,
            "read relationship {$resourceType}:{$id}:{$relationship}"
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertReplaceRelationship($resourceType, $id, $relationship)
    {
        $this->assertRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'PATCH'),
            $resourceType,
            "{$resourceType}:replace-relationship:$id",
            "{$resourceType}.relationships.$relationship.replace",
            $relationship
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     * @param bool $notFound
     */
    private function assertNotReplaceRelationship($resourceType, $id, $relationship, $notFound = true)
    {
        $this->assertNotRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'PATCH'),
            $notFound,
            "replace relationship {$resourceType}:{$id}:{$relationship}"
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertAddToRelationship($resourceType, $id, $relationship)
    {
        $this->assertRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'POST'),
            $resourceType,
            "{$resourceType}:add-relationship:$id",
            "{$resourceType}.relationships.$relationship.add",
            $relationship
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     * @param bool $notFound
     */
    private function assertNotAddToRelationship($resourceType, $id, $relationship, $notFound = true)
    {
        $this->assertNotRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'POST'),
            $notFound,
            "add to relationship {$resourceType}:{$id}:{$relationship}"
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertRemoveRelationship($resourceType, $id, $relationship)
    {
        $this->assertRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'DELETE'),
            $resourceType,
            "{$resourceType}:remove-relationship:$id",
            "{$resourceType}.relationships.$relationship.remove",
            $relationship
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     * @param bool $notFound
     */
    private function assertNotRemoveRelationship($resourceType, $id, $relationship, $notFound = true)
    {
        $this->assertNotRoute(
            Request::create("/$resourceType/$id/relationships/$relationship", 'DELETE'),
            $notFound,
            "delete relationship {$resourceType}:{$id}:{$relationship}"
        );
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertRelationship($resourceType, $id, $relationship)
    {
        $this->assertRelated($resourceType, $id, $relationship);
        $this->assertReadRelationship($resourceType, $id, $relationship);
        $this->assertReplaceRelationship($resourceType, $id, $relationship);
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertNotRelationship($resourceType, $id, $relationship)
    {
        $this->assertNotRelated($resourceType, $id, $relationship);
        $this->assertNotReadRelationship($resourceType, $id, $relationship);
        $this->assertNotReplaceRelationship($resourceType, $id, $relationship);
        $this->assertNotAddToRelationship($resourceType, $id, $relationship);
        $this->assertNotRemoveRelationship($resourceType, $id, $relationship);
    }

    /**
     * @param $resourceType
     * @param $relationship
     * @param $id
     */
    private function assertHasOne($resourceType, $id, $relationship)
    {
        $this->assertRelationship($resourceType, $id, $relationship);
        /** These should not be registered (i.e. ensure it has not been registered as a has-many */
        $this->assertNotAddToRelationship($resourceType, $id, $relationship, false);
        $this->assertNotRemoveRelationship($resourceType, $id, $relationship, false);
    }

    /**
     * @param $resourceType
     * @param $id
     * @param $relationship
     */
    private function assertHasMany($resourceType, $id, $relationship)
    {
        $this->assertRelationship($resourceType, $id, $relationship);
        $this->assertAddToRelationship($resourceType, $id, $relationship);
        $this->assertRemoveRelationship($resourceType, $id, $relationship);
    }

    /**
     * @param Route $route
     * @param $expected
     */
    private function assertAuthorizer(Route $route, $expected)
    {
        $this->assertMiddleware($route, "json-api.authorize:$expected");
    }

    /**
     * @param Route $route
     * @param $expected
     */
    private function assertValidator(Route $route, $expected)
    {
        $this->assertMiddleware($route, "json-api.validate:$expected");
    }

    /**
     * @param Route $route
     * @param $expected
     */
    private function assertMiddleware(Route $route, $expected)
    {
        $this->assertContains($expected, $route->middleware());
    }

    /**
     * @param Request $request
     * @param bool $notFound
     * @param string|null $message
     */
    private function assertNotRoute(Request $request, $notFound = true, $message = null)
    {
        if ($notFound) {
            $this->assertNotFound($request, $message);
        } else {
            $this->assertMethodNotAllowed($request, $message);
        }
    }

    /**
     * @param Request $request
     * @param string|null $message
     */
    private function assertNotFound(Request $request, $message = null)
    {
        $notFound = false;

        try {
            $this->router->getRoutes()->match($request);
        } catch (NotFoundHttpException $ex) {
            $notFound = true;
        }

        $this->assertTrue($notFound, $message ?: 'Route was found');
    }

    /**
     * @param Request $request
     * @param string|null $message
     */
    private function assertMethodNotAllowed(Request $request, $message = null)
    {
        $notAllowed = false;

        try {
            $this->router->getRoutes()->match($request);
        } catch (MethodNotAllowedHttpException $ex) {
            $notAllowed = true;
        }

        $this->assertTrue($notAllowed, $message ?: 'Route was found');
    }
}
