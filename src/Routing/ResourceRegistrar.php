<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Document\GeneratesRouteNames;
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

    use GeneratesRouteNames;

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
     * @param string|null $controller
     * @param array $options
     * @return void
     */
    public function resource($resourceType, $controller = null, array $options = [])
    {
        $controller = $controller ?: $this->controllerFor($resourceType);

        $this->registerIndex($resourceType, $controller);
        $this->registerResource($resourceType, $controller);
        $this->registerRelatedResource($resourceType, $controller);
        $this->registerRelationships($resourceType, $controller);
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerIndex($resourceType, $controller)
    {
        $uri = $this->indexUri($resourceType);
        $name = $this->indexRouteName($resourceType);
        $this->route($resourceType, 'get', $uri, $controller, 'index', $name);
        $this->route($resourceType, 'post', $uri, $controller, 'create');
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerResource($resourceType, $controller)
    {
        $uri = $this->resourceUri($resourceType);
        $name = $this->resourceRouteName($resourceType);
        $this->route($resourceType, 'get', $uri, $controller, 'read', $name);
        $this->route($resourceType, 'patch', $uri, $controller, 'update');
        $this->route($resourceType, 'delete', $uri, $controller, 'delete');
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerRelatedResource($resourceType, $controller)
    {
        $uri = $this->relatedResourceUri($resourceType);
        $name = $this->relatedResourceRouteName($resourceType);
        $this->route($resourceType, 'get', $uri, $controller, 'readRelatedResource', $name);
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerRelationships($resourceType, $controller)
    {
        $uri = $this->relationshipUri($resourceType);
        $name = $this->relationshipRouteName($resourceType);
        $this->route($resourceType, 'get', $uri, $controller, 'readRelationship', $name);
        $this->route($resourceType, 'patch', $uri, $controller, 'replaceRelationship');
        $this->route($resourceType, 'post', $uri, $controller, 'addToRelationship');
        $this->route($resourceType, 'delete', $uri, $controller, 'removeFromRelationship');
    }

    /**
     * @param $resourceType
     * @param $routerMethod
     * @param $uri
     * @param $controller
     * @param $controllerMethod
     * @param $as
     */
    protected function route(
        $resourceType,
        $routerMethod,
        $uri,
        $controller,
        $controllerMethod,
        $as = null
    ) {
        $options = ['uses' => sprintf('%s@%s', $controller, $controllerMethod)];

        if ($as) {
            $options['as'] = $as;
        }

        /** @var Route $route */
        $route = $this->router->{$routerMethod}($uri, $options);
        $route->defaults(self::PARAM_RESOURCE_TYPE, $resourceType);
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
