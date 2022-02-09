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

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;

/**
 * Class ResolverFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResolverFactory
{

    /**
     * Create a resolver.
     *
     * @param string $apiName
     * @param array $config
     * @return NamespaceResolver
     */
    public function __invoke($apiName, array $config)
    {
        $byResource = $config['by-resource'];

        if ('false-0.x' === $byResource) {
            throw new RuntimeException("The 'false-0.x' resolver option is no longer supported.");
        }

        return new NamespaceResolver(
            $config['namespace'],
            (array) $config['resources'],
            (bool) $byResource
        );
    }

}
