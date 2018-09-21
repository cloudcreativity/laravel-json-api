<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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
        $resolver = $this->factory->createResolver($apiName, $config);

        $api = new Api(
            $this->factory,
            new AggregateResolver($resolver),
            $apiName,
            $config['codecs'],
            Url::fromArray($config['url']),
            $config['use-eloquent'],
            $config['supported-ext'],
            $config['errors']
        );

        /** Attach resource providers to the API. */
        $this->createProviders($apiName)->registerAll($api);

        return $api;
    }

    /**
     * @param $apiName
     * @return ResourceProviders
     */
    public function createProviders($apiName)
    {
        return new ResourceProviders(
            $this->factory,
            $this->config->get($this->configKey($apiName, 'providers'))
        );
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
            'resources' => null,
            'use-eloquent' => true,
            'codecs' => null,
            'supported-ext' => null,
            'url' => null,
            'errors' => null,
        ], $config);

        if (!$config['namespace']) {
            $config['namespace'] = rtrim(app()->getNamespace(), '\\') . '\\JsonApi';
        }

        $config['url'] = $this->normalizeUrl((array) $config['url'], $host);
        $config['errors'] = array_replace($this->defaultErrors(), (array) $config['errors']);

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
}
