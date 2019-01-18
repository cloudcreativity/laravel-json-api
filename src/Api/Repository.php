<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Resolver\AggregateResolver;
use Illuminate\Contracts\Config\Repository as Config;

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
     * Repository constructor.
     *
     * @param Factory $factory
     * @param Config $config
     */
    public function __construct(Factory $factory, Config $config)
    {
        $this->factory = $factory;
        $this->config = $config;
    }

    /**
     * @param $apiName
     * @return bool
     */
    public function exists($apiName)
    {
        return $this->config->has($this->configKey($apiName));
    }

    /**
     * @param $apiName
     * @param string|null $host
     * @return Api
     */
    public function createApi($apiName, $host = null)
    {
        $config = $this->configFor($apiName);
        $config = $this->normalize($config, $host);
        $url = Url::fromArray($config['url']);
        $resolver = new AggregateResolver($this->factory->createResolver($apiName, $config));

        $api = new Api(
            $this->factory,
            $resolver,
            $apiName,
            EncodingList::fromArray($config['encoding'] ?? [], $url->toString()),
            DecodingList::fromArray($config['decoding'] ?? []),
            $url,
            Jobs::fromArray($config['jobs'] ?? []),
            $config['use-eloquent'],
            $config['supported-ext'],
            $config['errors'],
            $config['providers'] ?? []
        );

        /** Attach resource providers to the API. */
        $api->providers()->registerAll($api);

        return $api;
    }

    /**
     * @param $apiName
     * @return array
     */
    private function configFor($apiName)
    {
        $config = (array) $this->config->get($this->configKey($apiName));

        if (empty($config)) {
            throw new RuntimeException("JSON API '$apiName' does not exist.");
        }

        return $config;
    }

    /**
     * @param array $config
     * @param string|null $host
     * @return array
     */
    private function normalize(array $config, $host = null)
    {
        $config = array_replace([
            'namespace' => null,
            'by-resource' => true,
            'use-eloquent' => true,
            'supported-ext' => null,
        ], $config);

        if (!$config['namespace']) {
            $config['namespace'] = rtrim(app()->getNamespace(), '\\') . '\\JsonApi';
        }

        $config['resources'] = $this->normalizeResources($config['resources'] ?? [], $config);
        $config['url'] = $this->normalizeUrl($config['url'] ?? [], $host);
        $config['errors'] = array_replace($this->defaultErrors(), $config['errors'] ?? []);

        return $config;
    }

    /**
     * @param string $apiName
     * @param string|null $path
     * @return string
     */
    private function configKey($apiName, $path = null)
    {
        $key = "json-api-$apiName";

        return $path ? "$key.$path" : $key;
    }

    /**
     * @return array
     */
    private function defaultErrors()
    {
        return (array) $this->config->get('json-api-errors');
    }

    /**
     * @param array $url
     * @param string|null $host
     * @return array
     */
    private function normalizeUrl(array $url, $host = null)
    {
        $prependHost = false !== array_get($url, 'host');

        if ($host) {
            $url['host'] = $host;
        } elseif (!isset($url['host'])) {
            $url['host'] = $this->config->get('app.url');
        }

        return [
            'host' => $prependHost ? (string) $url['host'] : '',
            'namespace' => (string) array_get($url, 'namespace'),
            'name' => (string) array_get($url, 'name'),
        ];
    }

    /**
     * @param array $resources
     * @param array $config
     * @return array
     */
    private function normalizeResources(array $resources, array $config)
    {
        $jobs = isset($config['jobs']) ? Jobs::fromArray($config['jobs']) : null;

        if ($jobs && !isset($resources[$jobs->getResource()])) {
            $resources[$jobs->getResource()] = $jobs->getModel();
        }

        return $resources;
    }
}
