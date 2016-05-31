<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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
use CloudCreativity\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Services\JsonApiContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BootJsonApi
 * @package CloudCreativity\LaravelJsonApi
 */
class BootJsonApi
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param $urlNamespace
     *      the url namespace to add to the HTTP schema/host, e.g. '/api/v1'
     * @param $schemasName
     *      the name of the set of schemas to use, or empty to use the default set.
     * @return mixed
     */
    public function handle($request, Closure $next, $urlNamespace = null, $schemasName = null)
    {
        $urlPrefix = $this->urlPrefix($request, $urlNamespace);
        $schemas = $this->resolveSchemas($schemasName);
        $codecMatcher = $this->resolveCodecMatcher($schemas, $urlPrefix);
        $this->register($schemas, $codecMatcher, $urlPrefix);

        /** @var ContentNegotiatorInterface $negotiator */
        $negotiator = $this->container->make(ContentNegotiatorInterface::class);
        /** @var ServerRequestInterface $request */
        $serverRequest = $this->container->make(ServerRequestInterface::class);
        $negotiator->doContentNegotiation($codecMatcher, $serverRequest);

        return $next($request);
    }

    /**
     * @param Request $request
     * @param $urlNamespace
     * @return string
     */
    protected function urlPrefix(Request $request, $urlNamespace)
    {
        return $request->getSchemeAndHttpHost() . $urlNamespace;
    }

    /**
     * @param string|null $schemasName
     * @return ContainerInterface
     */
    protected function resolveSchemas($schemasName)
    {
        /** @var SchemasRepositoryInterface $repository */
        $repository = $this->container->make(SchemasRepositoryInterface::class);

        return $repository->getSchemas($schemasName);
    }

    /**
     * @param ContainerInterface $schemas
     * @param $urlPrefix
     * @return CodecMatcherInterface
     */
    protected function resolveCodecMatcher(ContainerInterface $schemas, $urlPrefix)
    {
        /** @var CodecMatcherRepositoryInterface $repository */
        $repository = $this->container->make(CodecMatcherRepositoryInterface::class);

        return $repository
            ->registerSchemas($schemas)
            ->registerUrlPrefix($urlPrefix)
            ->getCodecMatcher();
    }

    /**
     * @param ContainerInterface $schemas
     * @param CodecMatcherInterface $codecMatcher
     * @param $urlPrefix
     */
    private function register(
        ContainerInterface $schemas,
        CodecMatcherInterface $codecMatcher,
        $urlPrefix
    ) {
        $container = new JsonApiContainer($codecMatcher, $schemas, $urlPrefix);

        $this->container->instance(JsonApiContainer::class, $container);
    }
}
