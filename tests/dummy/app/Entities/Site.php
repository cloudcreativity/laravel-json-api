<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace DummyApp\Entities;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class Site
{

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string|null
     */
    private $domain;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @param string $slug
     * @param array $values
     * @return Site
     */
    public static function create($slug, array $values)
    {
        $site = new self($slug);
        $site->exchangeArray($values);

        return $site;
    }

    /**
     * Site constructor.
     *
     * @param string $slug
     */
    public function __construct($slug)
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('Expecting a non-empty slug');
        }

        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return (string) $this->domain;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return (string) $this->name;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function exchangeArray(array $values)
    {
        if ($domain = Arr::get($values, 'domain')) {
            $this->setDomain($domain);
        }

        if ($name = Arr::get($values, 'name')) {
            $this->setName($name);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain' => $this->getDomain(),
            'name' => $this->getName(),
        ];
    }

}
