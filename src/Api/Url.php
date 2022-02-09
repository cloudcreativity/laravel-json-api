<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Str;

/**
 * Class Url
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Url
{

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $name;

    /**
     * Create a URL from an array.
     *
     * @param array $url
     * @return Url
     */
    public static function fromArray(array $url): self
    {
        return new self(
            isset($url['host']) ? $url['host'] : '',
            isset($url['namespace']) ? $url['namespace'] : '',
            isset($url['name']) ? $url['name'] : ''
        );
    }

    /**
     * Url constructor.
     *
     * @param string $host
     * @param string $namespace
     * @param string $name
     */
    public function __construct(string $host, string $namespace, string $name)
    {
        $this->host = rtrim($host, '/');
        $this->namespace = $namespace ? '/' . ltrim($namespace, '/') : '';
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return rtrim($this->host . $this->namespace, '/');
    }

    /**
     * Replace route parameters in the URL namespace.
     *
     * @param iterable $parameters
     * @return Url
     */
    public function replace(iterable $parameters): self
    {
        if (!Str::contains($this->namespace, '{')) {
            return $this;
        }

        $copy = clone $this;

        foreach ($parameters as $key => $value) {
            $routeParamValue = $value;

            if ($value instanceof UrlRoutable) {
              $routeParamValue = $value->getRouteKey();
            }

            $copy->namespace = \str_replace('{' . $key . '}', $routeParamValue, $copy->namespace);
        }

        return $copy;
    }

    /**
     * @param $host
     * @return Url
     */
    public function withHost($host): self
    {
        $copy = clone $this;
        $copy->host = rtrim($host, '/');

        return $copy;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the base URI for a Guzzle client.
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->toString() . '/';
    }

    /**
     * Get the URL for the resource type.
     *
     * @param string $type
     * @param array $params
     * @return string
     */
    public function getResourceTypeUrl(string $type, array $params = []): string
    {
        return $this->url([$type], $params);
    }

    /**
     * Get the URL for the specified resource.
     *
     * @param string $type
     * @param mixed $id
     * @param array $params
     * @return string
     */
    public function getResourceUrl(string $type, $id, array $params = []): string
    {
        return $this->url([$type, $id], $params);
    }

    /**
     * Get the URI for a related resource.
     *
     * @param string $type
     * @param mixed $id
     * @param string $field
     * @param array $params
     * @return string
     */
    public function getRelatedUrl(string $type, $id, string $field, array $params = []): string
    {
        return $this->url([$type, $id, $field], $params);
    }

    /**
     * Get the URI for the resource's relationship.
     *
     * @param string $type
     * @param mixed $id
     * @param string $field
     * @param array $params
     * @return string
     */
    public function getRelationshipUri(string $type, $id, string $field, array $params = []): string
    {
        return $this->url([$type, $id, 'relationships', $field], $params);
    }

    /**
     * @param array $extra
     * @param array $params
     * @return string
     */
    private function url(array $extra, array $params = []): string
    {
        $url = collect([$this->toString()])->merge($extra)->map(function ($value) {
            return $value instanceof UrlRoutable ? $value->getRouteKey() : (string) $value;
        })->implode('/');

        return $params ? $url . '?' . http_build_query($params) : $url;
    }

}
