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

namespace CloudCreativity\LaravelJsonApi\Document\Concerns;

trait HasMeta
{

    /**
     * @var array|null
     */
    private $meta;

    /**
     * @return array|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param iterable|null $meta
     * @return $this
     */
    public function setMeta(?iterable $meta): self
    {
        $this->meta = collect($meta)->toArray() ?: null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasMeta(): bool
    {
        return !empty($this->meta);
    }
}
