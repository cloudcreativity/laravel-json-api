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

namespace CloudCreativity\LaravelJsonApi\Api;

/**
 * Class ResourceProvider
 *
 * @package CloudCreativity\LaravelJsonApi\Api
 */
abstract class ResourceProvider
{

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var bool
     */
    protected $byResource = true;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @return string
     */
    abstract protected function getRootNamespace();

    /**
     * @return ApiResources
     */
    public function getResources()
    {
        $resources = new ResourceMap($this->getRootNamespace(), $this->resources, $this->byResource);

        return $resources->all();
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
