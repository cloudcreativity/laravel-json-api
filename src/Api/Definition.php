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
 * Class Definition
 *
 * @package CloudCreativity\LaravelJsonApi\Api
 */
class Definition
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $rootNamespace;

    /**
     * @var bool
     */
    private $byResource;

    /**
     * @var ApiResources
     */
    private $resources;

    /**
     * @var array
     */
    private $codecs;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var bool
     */
    private $useEloquent;

    /**
     * @var string|null
     */
    private $urlPrefix;

    /**
     * @var string|null
     */
    private $supportedExt;

    /**
     * Definition constructor.
     *
     * @param $apiName
     * @param string $rootNamespace
     * @param array $resources
     * @param array $codecs
     * @param bool $byResource
     * @param bool $useEloquent
     * @param string|null $urlPrefix
     * @param string|null $supportedExt
     * @param array $errors
     */
    public function __construct(
        $apiName,
        $rootNamespace,
        array $resources,
        array $codecs,
        $byResource = true,
        $useEloquent = true,
        $urlPrefix = null,
        $supportedExt = null,
        array $errors
    ) {
        $resources = new ResourceMap($rootNamespace, $resources, $byResource);

        $this->name = $apiName;
        $this->rootNamespace = $rootNamespace;
        $this->resources = $resources->all();
        $this->codecs = $codecs;
        $this->byResource = $byResource;
        $this->useEloquent = $useEloquent;
        $this->urlPrefix = $urlPrefix;
        $this->supportedExt = $supportedExt;
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRootNamespace()
    {
        return $this->rootNamespace;
    }

    /**
     * @return bool
     */
    public function isByResource()
    {
        return $this->byResource;
    }

    /**
     * @return bool
     */
    public function isEloquent()
    {
        return $this->useEloquent;
    }

    /**
     * @return string|null
     */
    public function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @return string|null
     */
    public function getSupportedExt()
    {
        return $this->supportedExt;
    }

    /**
     * @return array
     */
    public function getCodecs()
    {
        return $this->codecs;
    }

    /**
     * @return ApiResources
     */
    public function getResources()
    {
        return clone $this->resources;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param ResourceProvider $provider
     * @return void
     */
    public function register(ResourceProvider $provider)
    {
        $this->mergeResources($provider->getResources());
        $this->mergeErrors($provider->getErrors());
    }

    /**
     * @param ApiResources $resources
     * @return void
     */
    protected function mergeResources(ApiResources $resources)
    {
        $this->resources = $resources->merge($this->resources);
    }

    /**
     * @param array $errors
     * @return void
     */
    protected function mergeErrors(array $errors)
    {
        $this->errors = array_replace($errors, $this->errors);
    }
}
