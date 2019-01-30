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

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Support\Arrayable;

class RelationshipRegistration implements Arrayable
{

    /**
     * @var array
     */
    private $options;

    /**
     * RelationshipRegistration constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param string $resourceType
     * @return $this
     */
    public function inverse(string $resourceType): self
    {
        $this->options['inverse'] = $resourceType;

        return $this;
    }

    /**
     * @param string ...$only
     * @return $this
     */
    public function only(string ...$only): self
    {
        $this->options['only'] = $only;

        return $this;
    }

    /**
     * @param string ...$except
     * @return $this
     */
    public function except(string ...$except): self
    {
        $this->options['except'] = $except;

        return $this;
    }

    /**
     * @return RelationshipRegistration
     */
    public function readOnly(): self
    {
        return $this->only('related', 'read');
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return $this->options;
    }

}
