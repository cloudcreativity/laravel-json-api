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

namespace CloudCreativity\LaravelJsonApi\Resolver;

use CloudCreativity\LaravelJsonApi\Utils\Str;

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
     * If not by resource, whether the type is included in the class name.
     *
     * From 2.0.0, the type will always be appended to the class name when resolution
     * is not by resource. This option is provided for backwards compatibility with the
     * 0.x and pre-1.0.0-alpha.3 versions.
     *
     * @var bool
     * @deprecated 2.0.0 Will always append the type to the class name.
     * @since 1.0.0-alpha.3
     */
    private $withType;

    /**
     * NamespaceResolver constructor.
     *
     * @param string $rootNamespace
     * @param array $resources
     * @param bool $byResource
     * @param bool $withType
     */
    public function __construct($rootNamespace, array $resources, $byResource = true, $withType = true)
    {
        parent::__construct($resources);
        $this->rootNamespace = $rootNamespace;
        $this->byResource = $byResource;
        $this->withType = $withType;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByName($name)
    {
        if (!$this->byResource) {
            return $this->resolve('Authorizer', $name);
        }

        $classified = Str::classify($name);

        return $this->append("{$classified}Authorizer");
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

        $classified = str_singular($classified);
        $class = $this->withType ? $classified . str_singular($unit) : $classified;

        return $this->append(sprintf('%s\%s', str_plural($unit), $class));
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
