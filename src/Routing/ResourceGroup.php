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

use CloudCreativity\JsonApi\Utils\Str;
use CloudCreativity\LaravelJsonApi\Api\ApiResource;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Fluent;

/**
 * Class ResourceGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceGroup
{

    use RegistersResources;

    /**
     * @var ApiResource
     */
    private $apiResource;

    /**
     * ResourceGroup constructor.
     *
     * @param string $resourceType
     * @param ApiResource $apiResource
     * @param Fluent $options
     */
    public function __construct($resourceType, ApiResource $apiResource, Fluent $options)
    {
        $this->resourceType = $resourceType;
        $this->apiResource = $apiResource;
        $this->options = $options;
    }

    /**
     * @param Registrar $router
     */
    public function addResource(Registrar $router)
    {
        $router->group($this->groupAction(), function ($router) {
            $this->addResourceRoutes($router);
            $this->addRelationshipRoutes($router);
        });
    }

    /**
     * @return array
     */
    protected function groupAction()
    {
        return [
            'middleware' => $this->middleware(),
            'as' => "{$this->resourceType}.",
            'prefix' => Str::dasherize($this->resourceType),
        ];
    }

    /**
     * @return array
     */
    protected function middleware()
    {
        $middleware = (array) $this->options->get('middleware');
        $authorizer = $this->authorizer();
        $validators = $this->validators();

        return array_merge($middleware, array_filter([
            $authorizer ? "json-api.authorize:$authorizer" : null,
            $validators ? "json-api.validate:$validators" : null,
        ]));
    }

    /**
     * @return string|null
     */
    protected function authorizer()
    {
        if ($authorizer = $this->options->get('authorizer')) {
            return $authorizer;
        }

        return $this->apiResource->getAuthorizerFqn() ?: $this->options->get('default-authorizer');
    }

    /**
     * @return string|null
     */
    protected function validators()
    {
        if ($validators = $this->options->get('validators')) {
            return $validators;
        }

        return $this->apiResource->getValidatorsFqn();
    }

    /**
     * @param Registrar $router
     */
    protected function addResourceRoutes(Registrar $router)
    {
        foreach($this->resourceActions() as $action) {
            $this->resourceRoute($router, $action);
        }
    }

    /**
     * @return array
     */
    protected function resourceActions()
    {
        return $this->diffActions(['index', 'create', 'read', 'update', 'delete'], $this->options);
    }

    /**
     * @param Registrar $router
     */
    protected function addRelationshipRoutes(Registrar $router)
    {
        $this->relationshipsGroup()->addRelationships($router);
    }

    /**
     * @return RelationshipsGroup
     */
    protected function relationshipsGroup()
    {
        return new RelationshipsGroup($this->resourceType, $this->options);
    }

    /**
     * @param Registrar $router
     * @param $action
     * @return Route
     */
    protected function resourceRoute(Registrar $router, $action)
    {
        return $this->createRoute(
            $router,
            $this->routeMethod($action),
            $this->routeUrl($action),
            $this->routeAction($action)
        );
    }

    /**
     * @param $action
     * @return string
     */
    protected function routeUrl($action)
    {
        if (in_array($action, ['index', 'create'], true)) {
            return $this->baseUrl();
        }

        return $this->resourceUrl();
    }

    /**
     * @param $action
     * @return array
     */
    protected function routeAction($action)
    {
        return [
            'uses' => $this->controllerAction($action),
            'as' => $action,
        ];
    }

    /**
     * @param $action
     * @return string
     */
    protected function routeMethod($action)
    {
        $methods = [
            'index' => 'get',
            'create' => 'post',
            'read' => 'get',
            'update' => 'patch',
            'delete' => 'delete',
        ];

        return $methods[$action];
    }

    /**
     * @param $action
     * @return string
     */
    protected function controllerAction($action)
    {
        return sprintf('%s@%s', $this->controller(), $action);
    }
}
