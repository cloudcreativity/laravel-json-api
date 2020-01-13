<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use Generator;
use IteratorAggregate;

class SiteRepository implements IteratorAggregate
{

    /**
     * @var array
     */
    private $sites = [];

    /**
     * @param $slug
     * @return Site|null
     */
    public function find($slug)
    {
        if (!isset($this->sites[$slug])) {
            return null;
        }

        return Site::create($slug, $this->sites[$slug]);
    }

    /**
     * @return array
     */
    public function findAll()
    {
        return iterator_to_array($this->all());
    }

    /**
     * @param Site $site
     * @return void
     */
    public function store(Site $site)
    {
        $this->sites[$site->getSlug()] = $site->toArray();
    }

    /**
     * @param Site|string $site
     * @return void
     */
    public function remove($site)
    {
        $slug = ($site instanceof Site) ? $site->getSlug() : $site;

        unset($this->sites[$slug]);
    }

    /**
     * @return array
     */
    public function all()
    {
        return iterator_to_array($this);
    }

    /**
     * @return Generator
     */
    public function getIterator()
    {
        foreach ($this->sites as $slug => $values) {
            yield $slug => Site::create($slug, $values);
        }
    }

}
