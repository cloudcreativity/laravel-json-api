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

use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Arr;

/**
 * Class Repository
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Repository
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var array
     */
    private $definitions = [];

    /**
     * Repository constructor.
     *
     * @param Config $config
     * @param Factory $factory
     */
    public function __construct(Config $config, Factory $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * @param $apiName
     * @param string|null $host
     * @return ApiInterface
     */
    public function retrieveApi($apiName, $host = null)
    {
        $definition = $this->retrieveDefinition($apiName);

        return $this->createApi($definition, $host);
    }

    /**
     * @param $apiName
     * @return Definition
     */
    public function retrieveDefinition($apiName)
    {
        if (isset($this->definitions[$apiName])) {
            return $this->definitions[$apiName];
        }

        $config = $this->configFor($apiName);
        $definition = $this->createDefinition($apiName, $config);
        $this->createProviders($config)->registerAll($definition);

        return $this->definitions[$apiName] = $definition;
    }

    /**
     * @param $apiName
     * @return ResourceProviders
     */
    public function retrieveProviders($apiName)
    {
        return $this->createProviders($this->configFor($apiName));
    }

    /**
     * @param Definition $definition
     * @param $host
     * @return ApiInterface
     */
    protected function createApi(Definition $definition, $host)
    {
        $resources = $definition->getResources();
        $schemas = $this->factory->createContainer($resources->getSchemas());
        $adapters = $this->factory->createAdapterContainer($resources->getAdapters());
        $urlPrefix = $this->mergeHostAndUrlPrefix($host, $definition->getUrlPrefix());
        $extensions = $definition->getSupportedExt();

        return $this->factory->createApi(
            $definition->getName(),
            $this->factory->createConfiguredCodecMatcher($schemas, $definition->getCodecs()),
            $schemas,
            $this->factory->createStore($adapters),
            $this->factory->createErrorRepository($definition->getErrors()),
            $extensions ? $this->factory->createSupportedExtensions($extensions) : null,
            $urlPrefix
        );
    }

    /**
     * @param $apiName
     * @param array $config
     * @return Definition
     */
    protected function createDefinition($apiName, array $config)
    {
        return new Definition(
            $apiName,
            $this->normalizeRootNamespace(Arr::get($config, 'namespace')),
            (array) Arr::get($config, 'resources'),
            (array) Arr::get($config, 'codecs'),
            (bool) Arr::get($config, 'by-resource', true),
            (bool) Arr::get($config, 'use-eloquent', true),
            Arr::get($config, 'url-prefix'),
            Arr::get($config, 'supported-ext'),
            $this->mergeErrors((array) Arr::get($config, 'errors'))
        );
    }

    /**
     * @param array $apiConfig
     * @return ResourceProviders
     */
    protected function createProviders(array $apiConfig)
    {
        $providers = (array) Arr::get($apiConfig, 'providers');

        return new ResourceProviders($this->factory, $providers);
    }

    /**
     * @param $apiName
     * @return array
     */
    protected function configFor($apiName)
    {
        if (empty($config = $this->retrieveConfig($apiName))) {
            throw new RuntimeException("JSON API '$apiName' does not exist.");
        }

        return $config;
    }

    /**
     * @param $apiName
     * @return array
     */
    protected function retrieveConfig($apiName)
    {
        return (array) $this->config->get($this->configKey($apiName));
    }

    /**
     * @param $apiName
     * @return string
     */
    protected function configKey($apiName)
    {
        return "json-api-$apiName";
    }

    /**
     * @return array
     */
    protected function defaultErrors()
    {
        return (array) $this->config->get('json-api-errors');
    }

    /**
     * @param array $errors
     * @return array
     */
    protected function mergeErrors(array $errors)
    {
        return array_replace($this->defaultErrors(), $errors);
    }

    /**
     * @param string|null $host
     * @param string $urlPrefix
     * @return string|null
     */
    protected function mergeHostAndUrlPrefix($host, $urlPrefix)
    {
        if ($host) {
            $host = rtrim($host, '/');
            $urlPrefix = $host . $urlPrefix;
        }

        return $urlPrefix ? $urlPrefix : null;
    }

    /**
     * @param $namespace
     * @return string
     */
    protected function normalizeRootNamespace($namespace)
    {
        return $namespace ?: rtrim(app()->getNamespace(), '\\') . '\\JsonApi';
    }
}
