<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Routing;

use Illuminate\Contracts\Routing\Registrar;

/**
 * Class ResourceRegistrar
 * @package CloudCreativity\JsonApi\Laravel
 */
class ResourceRegistrar
{

    /** Options key for an array of has-one relation names */
    const HAS_ONE = 'hasOne';
    /** Options key for an array of has-many relation names */
    const HAS_MANY = 'hasMany';

    /**
     * @var Registrar
     */
    protected $router;

    /**
     * @type array
     */
    protected $resourceDefaults;

    /**
     * @type array
     */
    protected $rootUrlMethods = ['index', 'create'];

    /**
     * @param Registrar $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;

        $this->resourceDefaults = [
            'index'  => 'get',
            'create' => 'post',
            'read'   => 'get',
            'update' => 'patch',
            'delete' => 'delete',
        ];
    }

    /**
     * @param $name
     * @param $controller
     * @param array $options
     * @return void
     */
    public function resource($name, $controller, array $options = [])
    {
        $rootUrl = sprintf('/%s', $name);
        $objectUrl = sprintf('%s/{id}', $rootUrl);
        $hasOne = isset($options[static::HAS_ONE]) ? (array) $options[static::HAS_ONE] : [];
        $hasMany = isset($options[static::HAS_MANY]) ? (array) $options[static::HAS_MANY] : [];

        $this->registerResource($rootUrl, $objectUrl, $controller, $options)
            ->registerHasOne($objectUrl, $controller, $hasOne)
            ->registerHasMany($objectUrl, $controller, $hasMany);
    }

    /**
     * @param $rootUrl
     * @param $objectUrl
     * @param $controller
     * @param $options
     * @return $this
     */
    private function registerResource($rootUrl, $objectUrl, $controller, $options)
    {
        foreach ($this->getResourceMethods($options) as $method) {
            $url = in_array($method,
                $this->rootUrlMethods) ? $rootUrl : $objectUrl;
            call_user_func_array(
                [$this->router, $this->resourceDefaults[$method],],
                [$url, $controller . '@' . $method,]
            );
        }

        return $this;
    }

    /**
     * @param $objectUrl
     * @param $controller
     * @param array $relations
     * @return $this
     */
    private function registerHasOne($objectUrl, $controller, array $relations)
    {
        foreach ($relations as $relation) {
            $related = sprintf('%s/%s', $objectUrl, $relation);
            $identifier = sprintf('%s/relationships/%s', $objectUrl, $relation);
            $name = ucfirst(camel_case($relation));

            $this->router->get($related, sprintf('%s@read%s', $controller, $name));
            $this->router->get($identifier, sprintf('%s@read%sRelationship', $controller, $name));
            $this->router->patch($identifier, sprintf('%s@update%sRelationship', $controller, $name));
        }

        return $this;
    }

    /**
     * @param $objectUrl
     * @param $controller
     * @param array $relations
     * @return $this
     */
    private function registerHasMany($objectUrl, $controller, array $relations)
    {
        foreach ($relations as $relation) {
            $related = sprintf('%s/%s', $objectUrl, $relation);
            $identifier = sprintf('%s/relationships/%s', $objectUrl, $relation);
            $name = ucfirst(camel_case($relation));

            $this->router->get($related, sprintf('%s@read%s', $controller, $name));
            $this->router->get($identifier, sprintf('%s@read%sRelationship', $controller, $name));
            $this->router->patch($identifier, sprintf('%s@update%sRelationship', $controller, $name));
            $this->router->delete($identifier, sprintf('%s@delete%sRelationship', $controller, $name));
        }

        return $this;
    }

    /**
     * Get the applicable resource methods.
     *
     * @param $options
     * @return array
     */
    protected function getResourceMethods($options)
    {
        $defaults = array_keys($this->resourceDefaults);
        if (isset($options['only'])) {
            return array_intersect($defaults, (array) $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, (array) $options['except']);
        }
        return $defaults;
    }
}
