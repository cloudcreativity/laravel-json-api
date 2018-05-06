<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Auth\HandlesAuthorizers;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Class AuthorizeRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Authorize
{

    use HandlesAuthorizers;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestInterface
     */
    private $jsonApiRequest;

    /**
     * Authorize constructor.
     *
     * @param ContainerInterface $container
     * @param RequestInterface $request
     */
    public function __construct(ContainerInterface $container, RequestInterface $request)
    {
        $this->container = $container;
        $this->jsonApiRequest = $request;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $authorizer
     * @return mixed
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, $authorizer)
    {
        $this->authorizeRequest(
            $this->container->getAuthorizerByName($authorizer),
            $this->jsonApiRequest,
            $request
        );

        return $next($request);
    }

}
