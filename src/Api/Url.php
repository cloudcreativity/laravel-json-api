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
    public static function fromArray(array $url)
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
    public function __construct($host, $namespace, $name)
    {
        $this->host = rtrim($host, '/');
        $this->namespace = $namespace ? '/' . ltrim($namespace, '/') : null;
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
    public function toString()
    {
        return rtrim($this->host . $this->namespace, '/');
    }

    /**
     * @param $host
     * @return Url
     */
    public function withHost($host)
    {
        $copy = clone $this;
        $copy->host = rtrim($host, '/');

        return $copy;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
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
    public function getBaseUri()
    {
        return $this->toString() . '/';
    }


}
