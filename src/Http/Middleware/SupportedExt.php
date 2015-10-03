<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use CloudCreativity\JsonApi\Integration\EnvironmentService;
use Neomerx\JsonApi\Parameters\SupportedExtensions;
use RuntimeException;

/**
 * Class SupportedExt
 * @package CloudCreativity\JsonApi\Laravel
 */
class SupportedExt
{

    /**
     * @var EnvironmentService
     */
    private $environment;

    /**
     * @param EnvironmentInterface $env
     */
    public function __construct(EnvironmentInterface $env)
    {
        if (!$env instanceof EnvironmentService) {
            throw new RuntimeException(sprintf('%s is built to work with the %s instance of %s.', static::class, EnvironmentService::class, EnvironmentInterface::class));
        }

        $this->environment = $env;
    }

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $args = array_slice(func_get_args(), 2);
        $extensions = implode(',', $args);

        if ($extensions) {
            $this->environment->registerSupportedExtensions(new SupportedExtensions($extensions));
        }

        return $next($request);
    }
}
