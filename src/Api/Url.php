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
        return $this->host . $this->namespace;
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


}
