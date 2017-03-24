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

use CloudCreativity\LaravelJsonApi\Utils\RouteName;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Class ResourceRegistrar
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceRegistrar
{

    const KEYWORD_RELATIONSHIPS = 'relationships';
    const PARAM_RESOURCE_TYPE = 'resource_type';
    const PARAM_RESOURCE_ID = 'resource_id';
    const PARAM_RELATIONSHIP_NAME = 'relationship_name';

    /**
     * @var Registrar
     */
    protected $router;

    /**
     * @param Registrar $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for the supplied resource type
     *
     * @param string $resourceType
     * @param array $options
     * @return void
     */
    public function resource($resourceType, array $options = [])
    {
        $middleware = $this->resourceMiddleware($resourceType, $options);

        $this->router->group(['middleware' => $middleware], function () use ($resourceType, $options) {
            $this->registerIndex($resourceType, $options);
            $this->registerResource($resourceType, $options);
            $this->registerAllRelationships($resourceType, $options);
        });
    }

    /**
     * @param $resourceType
     * @param array $options
     * @return array
     */
    protected function resourceMiddleware($resourceType, array $options)
    {
        $authorizer = isset($options['authorizer']) ? $options['authorizer'] : null;
        $validators = isset($options['validators']) ? $options['validators'] : null;

        return array_filter([
            $authorizer ? "json-api.authorize:$authorizer" : null,
            $validators ? "json-api.validate:$validators" : null,
        ]);
    }

    /**
     * @param $resourceType
     * @param array $options
     */
    protected function registerIndex($resourceType, array $options)
    {
        $uri = $this->indexUri($resourceType);

        $this->route($resourceType, 'get', $uri, 'index', $options)
            ->name(RouteName::index($resourceType));

        $this->route($resourceType, 'post', $uri, 'create', $options)
            ->name(RouteName::create($resourceType));
    }

    /**
     * @param $resourceType
     * @param array $options
     */
    protected function registerResource($resourceType, array $options)
    {
        $uri = $this->resourceUri($resourceType);

        $this->resourceRoute($resourceType, 'get', $uri, 'read', $options)
            ->name(RouteName::read($resourceType));

        $this->resourceRoute($resourceType, 'patch', $uri, 'update', $options)
            ->name(RouteName::update($resourceType));

        $this->resourceRoute($resourceType, 'delete', $uri, 'delete', $options)
            ->name(RouteName::delete($resourceType));
    }

    /**
     * @param $resourceType
     * @param array $relationships
     * @param array $options
     */
    protected function registerRelatedResource($resourceType, array $relationships, array $options)
    {
        $uri = $this->relatedResourceUri($resourceType);

        $this->resourceRoute($resourceType, 'get', $uri, 'readRelatedResource', $options)
            ->where(self::PARAM_RELATIONSHIP_NAME, implode('|', $relationships))
            ->name(RouteName::related($resourceType));
    }

    /**
     * @param $resourceType
     * @param array $options
     */
    protected function registerAllRelationships($resourceType, array $options)
    {
        $hasOne = isset($options['has-one']) ? (array) $options['has-one'] : [];
        $hasMany = isset($options['has-many']) ? (array) $options['has-many'] : [];

        if ($all = array_merge($hasOne, $hasMany)) {
            $this->registerRelationships($resourceType, $all, $options);
        }

        if ($hasMany) {
            $this->registerHasMany($resourceType, $hasMany, $options);
        }
    }

    /**
     * Register routes that are common to all relationships (has-one and has-many)
     *
     * @param $resourceType
     * @param string[] $relationships
     * @param array $options
     */
    protected function registerRelationships($resourceType, array $relationships, array $options)
    {
        $uri = $this->relationshipUri($resourceType);
        $this->registerRelatedResource($resourceType, $relationships, $options);

        /** Read relationship... */
        $this->resourceRoute($resourceType, 'get', $uri, 'readRelationship', $options)
            ->where(self::PARAM_RELATIONSHIP_NAME, implode('|', $relationships))
            ->name(RouteName::readRelationship($resourceType));

        /** Replace relationship name... */
        $this->resourceRoute($resourceType, 'patch', $uri, 'replaceRelationship', $options)
            ->where(self::PARAM_RELATIONSHIP_NAME, implode('|', $relationships))
            ->name(RouteName::replaceRelationship($resourceType));
    }

    /**
     * Register routes that only exist for a has-many relationship.
     *
     * @param $resourceType
     * @param string[] $relationships
     * @param array $options
     */
    protected function registerHasMany($resourceType, array $relationships, array $options)
    {
        $uri = $this->relationshipUri($resourceType);

        /** Add to relationship... */
        $this->resourceRoute($resourceType, 'post', $uri, 'addToRelationship', $options)
            ->where(self::PARAM_RELATIONSHIP_NAME, implode('|', $relationships))
            ->name(RouteName::addRelationship($resourceType));

        /** Remove from relationship... */
        $this->resourceRoute($resourceType, 'delete', $uri, 'removeFromRelationship', $options)
            ->where(self::PARAM_RELATIONSHIP_NAME, implode('|', $relationships))
            ->name(RouteName::removeRelationship($resourceType));
    }

    /**
     * @param $resourceType
     * @param $routerMethod
     * @param $uri
     * @param $controllerMethod
     * @param array $options
     * @return Route
     */
    protected function route(
        $resourceType,
        $routerMethod,
        $uri,
        $controllerMethod,
        array $options
    ) {
        $controller = isset($options['controller']) ? $options['controller'] : $this->controllerFor($resourceType);
        $options = ['uses' => sprintf('%s@%s', $controller, $controllerMethod)];

        /** @var Route $route */
        $route = $this->router->{$routerMethod}($uri, $options);
        $route->defaults(self::PARAM_RESOURCE_TYPE, $resourceType);

        return $route;
    }

    /**
     * @param $resourceType
     * @param $routerMethod
     * @param $uri
     * @param $controllerMethod
     * @param array $options
     * @return Route
     */
    protected function resourceRoute(
        $resourceType,
        $routerMethod,
        $uri,
        $controllerMethod,
        array $options
    ) {
        $route = $this->route($resourceType, $routerMethod, $uri, $controllerMethod, $options);

        if (isset($options['id'])) {
            $route->where(self::PARAM_RESOURCE_ID, $options['id']);
        }

        return $route;
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function indexUri($resourceType)
    {
        return sprintf('/%s', $resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function resourceUri($resourceType)
    {
        return sprintf('%s/{%s}', $this->indexUri($resourceType), self::PARAM_RESOURCE_ID);
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function relatedResourceUri($resourceType)
    {
        return sprintf(
            '%s/{%s}/{%s}',
            $this->indexUri($resourceType),
            self::PARAM_RESOURCE_ID,
            self::PARAM_RELATIONSHIP_NAME
        );
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function relationshipUri($resourceType)
    {
        return sprintf(
            '%s/{%s}/%s/{%s}',
            $this->indexUri($resourceType),
            self::PARAM_RESOURCE_ID,
            self::KEYWORD_RELATIONSHIPS,
            self::PARAM_RELATIONSHIP_NAME
        );
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function controllerFor($resourceType)
    {
        return Str::studly($resourceType) . 'Controller';
    }

}
