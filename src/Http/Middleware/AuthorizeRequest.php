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
use CloudCreativity\JsonApi\Contracts\Authorizer\AuthorizerInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Middleware\AuthorizesRequests;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * Class AuthorizeRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AuthorizeRequest
{

    use AuthorizesRequests;

    /**
     * @var Container
     */
    protected $container;

    /**
     * AuthorizeRequest constructor.
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
     * @param string $authorizer
     * @return mixed
     */
    public function handle($request, Closure $next, $authorizer)
    {
        $this->authorize(
            $this->container->make(InboundRequestInterface::class),
            $this->container->make(StoreInterface::class),
            $this->resolveAuthorizer($authorizer)
        );

        return $next($request);
    }

    /**
     * @param $name
     * @return AuthorizerInterface
     */
    protected function resolveAuthorizer($name)
    {
        $authorizer = $this->container->make($name);

        if (!$authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Authorizer '$name' is not an authorizer instance.");
        }

        return $authorizer;
    }
}
