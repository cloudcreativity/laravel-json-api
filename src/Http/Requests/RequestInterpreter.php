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
     * RequestInterpreter constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
        $name = $this->request->route(ResourceRegistrar::PARAM_RESOURCE_TYPE);

        if (empty($name)) {
            throw new RuntimeException('No matching resource type from the current route name.');
        }

        return $name;
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
