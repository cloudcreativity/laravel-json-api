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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Middleware\ValidatesRequests;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Container\Container;
use Illuminate\Http\Request;

/**
 * Class ValidateRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidateRequest
{

    use ValidatesRequests;

    /**
     * @var Container
     */
    protected $container;

    /**
     * ValidateRequest constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param string $validators
     * @return mixed
     */
    public function handle($request, Closure $next, $validators)
    {
        /** @var JsonApiService $service */
        $service = $this->container->make(JsonApiService::class);

        $this->validate(
            $service->getRequestInterpreter(),
            $service->getRequest(),
            $this->resolveValidators($validators)
        );

        return $next($request);
    }

    /**
     * @param $name
     * @return ValidatorProviderInterface
     */
    protected function resolveValidators($name)
    {
        $validators = $this->container->make($name);

        if (!$validators instanceof ValidatorProviderInterface) {
            throw new RuntimeException("Validators '$name' is not a validator provider instance.");
        }

        return $validators;
    }
}
