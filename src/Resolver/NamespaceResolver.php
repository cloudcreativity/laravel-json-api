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

namespace CloudCreativity\LaravelJsonApi\Resolver;

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Support\Str as IlluminateStr;

/**
 * Class NamespaceResolver
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class NamespaceResolver extends AbstractResolver
{

    /**
     * @var string
     */
    private $rootNamespace;

    /**
     * @var bool
     */
    private $byResource;

    /**
     * NamespaceResolver constructor.
     *
     * @param string $rootNamespace
     * @param array $resources
     * @param bool $byResource
     */
    public function __construct($rootNamespace, array $resources, $byResource = true)
    {
        parent::__construct($resources);
        $this->rootNamespace = $rootNamespace;
        $this->byResource = $byResource;
    }

    /**
     * @inheritDoc
     */
    protected function resolve($unit, $resourceType)
    {
        $classified = Str::classify($resourceType);

        if ($this->byResource) {
            return $this->append($classified . '\\' . $unit);
        }

        $classified = IlluminateStr::singular($classified);
        $class = $classified . IlluminateStr::singular($unit);

        return $this->append(sprintf('%s\%s', IlluminateStr::plural($unit), $class));
    }

    /**
     * @inheritdoc
     */
    protected function resolveName($unit, $name)
    {
        if (!$this->byResource) {
            return $this->resolve($unit, $name);
        }

        $classified = Str::classify($name);

        return $this->append($classified . $unit);
    }

    /**
     * Append the string to the root namespace.
     *
     * @param $string
     * @return string
     */
    protected function append($string)
    {
        $namespace = rtrim($this->rootNamespace, '\\');

        return "{$namespace}\\{$string}";
    }

}
