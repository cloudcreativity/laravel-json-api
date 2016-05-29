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

use Illuminate\Contracts\Routing\Registrar;

/**
 * Class ResourceRegistrar
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceRegistrar
{

    const KEYWORD_RELATIONSHIPS = 'relationships';
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
     * @param $resourceType
     * @param $controller
     * @param array $options
     * @return void
     */
    public function resource($resourceType, $controller, array $options = [])
    {
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
        $this->route('get', $uri, $controller, 'index', $this->resourceAlias($resourceType, 'index'));
        $this->route('post', $uri, $controller, 'create', $this->resourceAlias($resourceType, 'create'));
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerResource($resourceType, $controller)
    {
        $uri = $this->resourceUri($resourceType);
        $this->route('get', $uri, $controller, 'read', $this->resourceAlias($resourceType, 'read'));
        $this->route('patch', $uri, $controller, 'update', $this->resourceAlias($resourceType, 'update'));
        $this->route('delete', $uri, $controller, 'delete', $this->resourceAlias($resourceType, 'delete'));
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerRelatedResource($resourceType, $controller)
    {
        $uri = $this->relatedResourceUri($resourceType);
        $this->route('get', $uri, $controller, 'readRelatedResource', $this->resourceAlias($resourceType, 'related'));
    }

    /**
     * @param $resourceType
     * @param $controller
     */
    protected function registerRelationships($resourceType, $controller)
    {
        $uri = $this->relationshipUri($resourceType);
        $this->route('get', $uri, $controller, 'readRelationship', $this->relationshipAlias($resourceType, 'read'));
        $this->route('patch', $uri, $controller, 'replaceRelationship', $this->relationshipAlias($resourceType, 'replace'));
        $this->route('post', $uri, $controller, 'addToRelationship', $this->relationshipAlias($resourceType, 'add'));
        $this->route('delete', $uri, $controller, 'removeFromRelationship', $this->relationshipAlias($resourceType, 'remove'));
    }

    /**
     * @param $routerMethod
     * @param $uri
     * @param $controller
     * @param $controllerMethod
     * @param $as
     */
    protected function route(
        $routerMethod,
        $uri,
        $controller,
        $controllerMethod,
        $as = null
    ) {
        $this->router->{$routerMethod}($uri, [
            'uses' => sprintf('%s@%s', $controller, $controllerMethod),
            'as' => $as,
        ]);
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
     * @param $alias
     * @return string
     */
    protected function resourceAlias($resourceType, $alias)
    {
        return sprintf('%s.%s', $resourceType, $alias);
    }

    /**
     * @param $resourceType
     * @param $alias
     * @return string
     */
    protected function relationshipAlias($resourceType, $alias)
    {
        return sprintf('%s.relationship.%s', $resourceType, $alias);
    }
}
