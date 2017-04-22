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
use CloudCreativity\LaravelJsonApi\Api\ApiResource;
use CloudCreativity\LaravelJsonApi\Api\ApiResources;
use CloudCreativity\LaravelJsonApi\Api\Definition;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup as Api;
use CloudCreativity\LaravelJsonApi\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use PHPUnit_Framework_MockObject_MockObject as Mock;
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

    /**
     * @var Mock
     */
    private $definition;

    /**
     * @var ApiResources
     */
    private $resources;

    protected function setUp()
    {
        $this->definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $this->definition
            ->method('getResources')
            ->willReturn($this->resources = new ApiResources());

        $repository = $this->getMockBuilder(Repository::class)->disableOriginalConstructor()->getMock();
        $repository->method('retrieveDefinition')->with('v1')->willReturn($this->definition);

        /** @var Dispatcher $events */
        $events = $this->getMockBuilder(Dispatcher::class)->getMock();
        $this->router = new Router($events);

        /** @var Repository $repository */
        $this->registrar = new ResourceRegistrar($this->router, $repository);

        /** Add some default resources... */
        $this->withResource('posts')->withResource('comments')->withResource('tags');
    }

    public function testApi()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts');
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertApi($route, 'v1');
    }

    public function testResources()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts');
            $api->resource('comments');
        });

        $this->assertResource('posts', '1');
        $this->assertResource('comments', '2');
    }

    public function testNotAResource()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts');
            $api->resource('comments');
        });

        $this->assertNotResource('tags', '1');
    }

    public function testOnlyOption()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
           $api->resource('posts', [
               'only' => ['index', 'read'],
           ]);
        });

        $this->assertIndex('posts');
        $this->assertRead('posts', '1');
        // the following should be method not allowed:
        $this->assertNotCreate('posts', false);
        $this->assertNotUpdate('posts', '1', false);
        $this->assertNotDelete('posts', '1', false);
    }

    public function testExceptOption()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'except' => ['create', 'delete'],
            ]);
        });

        $this->assertIndex('posts');
        $this->assertRead('posts', '1');
        $this->assertUpdate('posts', '1');
        // the following should be method not allowed:
        $this->assertNotCreate('posts', false);
        $this->assertNotDelete('posts', '1', false);
    }

    public function testRelationships()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
            $api->resource('comments', [
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
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'has-one' => ['last-comment'],
                'has-many' => ['recent-comments'],
            ]);
        });

        $this->assertHasOne('posts', '1', 'last-comment');
        $this->assertHasMany('posts', '1', 'recent-comments');
    }

    public function testNotARelationship()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'has-one' => 'author',
                'has-many' => ['comments', 'tags'],
            ]);
        });

        $this->assertNotRelationship('posts', '1', 'site');
    }

    public function testRelationshipsOnlyOption()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'has-one' => [
                    'author' => ['only' => 'related'],
                ],
                'has-many' => [
                    'comments',
                    'tags' => ['only' => ['related', 'read']],
                ],
            ]);
        });

        $this->assertHasMany('posts', '1', 'comments');

        $this->assertRelated('posts', '1', 'author');
        // these should be not found as we haven't registered any "relationship" endpoints
        $this->assertNotReadRelationship('posts', '1', 'author');
        $this->assertNotReplaceRelationship('posts', '1', 'author');

        $this->assertRelated('posts', '1', 'tags');
        $this->assertReadRelationship('posts', '1', 'tags');
        // method not allowed for the following...
        $this->assertNotReplaceRelationship('posts', '1', 'tags', false);
        $this->assertNotAddToRelationship('posts', '1', 'tags', false);
        $this->assertNotRemoveRelationship('posts', '1', 'tags', false);
    }

    public function testRelationshipsExceptOption()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'has-one' => [
                    'author' => ['except' => 'replace'],
                ],
                'has-many' => [
                    'comments',
                    'tags' => ['except' => ['add', 'remove']],
                ],
            ]);
        });

        $this->assertHasMany('posts', '1', 'comments');

        $this->assertRelated('posts', '1', 'author');
        $this->assertReadRelationship('posts', '1', 'author');
        // method not allowed for the following...
        $this->assertNotReplaceRelationship('posts', '1', 'author', false);

        $this->assertRelated('posts', '1', 'tags');
        $this->assertReadRelationship('posts', '1', 'tags');
        $this->assertReplaceRelationship('posts', '1', 'tags');
        // method not allowed for the following...
        $this->assertNotAddToRelationship('posts', '1', 'tags', false);
        $this->assertNotRemoveRelationship('posts', '1', 'tags', false);
    }

    public function testSpecifiedController()
    {
        $this->registrar->api('v1', [], function (Api $api) {
            $api->resource('posts', [
                'controller' => PostsController::class,
                'has-one' => 'author',
                'has-many' => 'comments',
            ]);
        });

        $this->assertResource('posts', '1');
        $this->assertHasOne('posts', '1', 'author');
        $this->assertHasMany('posts', '1', 'comments');
    }

    public function testControllerInDifferentNamespace()
    {
        $this->registrar->api('v1', ['namespace' => 'Foo\Bar'], function (Api $api) {
            $api->resource('posts', [
                'controller' => '\\' . PostsController::class,
                'has-one' => 'author',
                'has-many' => 'comments',
            ]);
        });

        $this->assertResource('posts', '1');
        $this->assertHasOne('posts', '1', 'author');
        $this->assertHasMany('posts', '1', 'comments');
    }

    public function testSpecifiedAuthorizer()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'authorizer' => 'App\JsonApi\GenericAuthorizer',
            ]);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertAuthorizer($route, 'App\JsonApi\GenericAuthorizer');
    }

    public function testDefaultAuthorizer()
    {
        $this->withResource('comments', 'CommentsAuthorizer');

        $this->registrar->api('v1', [
            'namespace' => 'App\Http\Controllers',
            'authorizer' => 'App\JsonApi\GenericAuthorizer',
        ], function (Api $api) {
            $api->resource('posts');
            $api->resource('comments');
        });

        /** Posts should have the generic authorizer */
        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertAuthorizer($route, 'App\JsonApi\GenericAuthorizer');

        /** Comments should have its own authorizer */
        $route = $this->matchRoute(Request::create('/comments'), 'comments.index');
        $this->assertAuthorizer($route, 'CommentsAuthorizer');
    }

    public function testResourceAuthorizer()
    {
        $this->withResource('posts', 'PostsAuthorizer')
            ->withResource('comments', 'CommentsAuthorizer');

        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts');
            $api->resource('comments');
            $api->resource('tags', ['authorizer' => 'SomeOtherAuthorizer']);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertAuthorizer($route, 'PostsAuthorizer');

        $route = $this->matchRoute(Request::create('/comments'), 'comments.index');
        $this->assertAuthorizer($route, 'CommentsAuthorizer');

        $route = $this->matchRoute(Request::create('/tags'), 'tags.index');
        $this->assertAuthorizer($route, 'SomeOtherAuthorizer');
    }

    public function testSpecifiedValidators()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
                'validators' => 'App\JsonApi\GenericValidator',
            ]);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertValidator($route, 'App\JsonApi\GenericValidator');
    }

    public function testResourceValidators()
    {
        $this->withResource('posts', null, 'PostsValidators')
            ->withResource('comments', null, 'CommentsValidators');

        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts');
            $api->resource('comments');
            $api->resource('tags', ['validators' => 'SomeOtherValidators']);
        });

        $route = $this->matchRoute(Request::create('/posts'), 'posts.index');
        $this->assertValidator($route, 'PostsValidators');

        $route = $this->matchRoute(Request::create('/comments'), 'comments.index');
        $this->assertValidator($route, 'CommentsValidators');

        $route = $this->matchRoute(Request::create('/tags'), 'tags.index');
        $this->assertValidator($route, 'SomeOtherValidators');
    }

    public function testWithIdConstraint()
    {
        $this->registrar->api('v1', ['namespace' => 'App\Http\Controllers'], function (Api $api) {
            $api->resource('posts', [
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
        $this->assertNotRoute(Request::create("/{$resourceType}", 'POST'), $notFound, "create {$resourceType}");
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
    private function assertApi(Route $route, $expected)
    {
        $this->assertMiddleware($route, "json-api:$expected");
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

    /**
     * @param $resourceType
     * @param string|null $authorizer
     * @param string|null $validators
     * @return $this
     */
    private function withResource($resourceType, $authorizer = null, $validators = null)
    {
        $resource = $this->getMockBuilder(ApiResource::class)->disableOriginalConstructor()->getMock();
        $resource->method('getResourceType')->willReturn($resourceType);
        $resource->method('getAuthorizerFqn')->willReturn($authorizer);
        $resource->method('getValidatorsFqn')->willReturn($validators);

        /** @var ApiResource $resource */
        $this->resources->add($resource);

        return $this;
    }
}
