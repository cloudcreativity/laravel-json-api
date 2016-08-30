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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Requests\AbstractRequestInterpreter;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

/**
 * Class RequestInterpreter
 * @package CloudCreativity\LaravelJsonApi
 */
final class RequestInterpreter extends AbstractRequestInterpreter
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Router
     */
    private $router;

    /**
     * RequestInterpreter constructor.
     * @param Request $request
     * @param Router $router
     */
    public function __construct(Request $request, Router $router)
    {
        $this->request = $request;
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    protected function isMethod($method)
    {
        return $this->request->isMethod($method);
    }

    /**
     * @return string
     */
    public function getResourceType()
    {
        $name = $this->router->getCurrentRoute()->getName();

        preg_match('/([a-zA-Z\-_]+).{1}[a-z]+$/', $name, $matches);

        if (!isset($matches[1])) {
            throw new RuntimeException('No matching resource type from the current route name.');
        }

        return $matches[1];
    }

    /**
     * @inheritDoc
     */
    public function getResourceId()
    {
        return $this->request->route(ResourceRegistrar::PARAM_RESOURCE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getRelationshipName()
    {
        return $this->request->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    /**
     * @inheritDoc
     */
    public function isRelationshipData()
    {
        return $this->isRelationship() && $this->request->is('*/relationships/*');
    }

}
