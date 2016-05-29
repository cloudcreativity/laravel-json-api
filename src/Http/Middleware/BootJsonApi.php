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
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Services\JsonApiContainer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;

/**
 * Class InitCodecMatcher
 * @package CloudCreativity\JsonApi\Laravel
 */
class BootJsonApi
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var CodecMatcherRepositoryInterface
     */
    private $codecMatcherRepository;

    /**
     * @var SchemasRepositoryInterface
     */
    private $schemasRepository;

    /**
     * @param Container $container
     * @param CodecMatcherRepositoryInterface $codecMatcherRepository
     * @param SchemasRepositoryInterface $schemasRepository
     */
    public function __construct(
        Container $container,
        CodecMatcherRepositoryInterface $codecMatcherRepository,
        SchemasRepositoryInterface $schemasRepository
    ) {
        $this->container = $container;
        $this->codecMatcherRepository = $codecMatcherRepository;
        $this->schemasRepository = $schemasRepository;
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
        $schemasName = ($schemasName) ?: null;
        $urlPrefix = $this->urlPrefix($request, $urlNamespace);

        $this->register($schemasName, $urlPrefix);

        return $next($request);
    }

    /**
     * @param $schemasName
     * @param $urlPrefix
     */
    private function register($schemasName, $urlPrefix)
    {
        $schemaContainer = $this
            ->schemasRepository
            ->getSchemas($schemasName);

        $codecMatcher = $this
            ->codecMatcherRepository
            ->registerSchemas($schemaContainer)
            ->registerUrlPrefix($urlPrefix)
            ->getCodecMatcher();

        $container = new JsonApiContainer(
            $codecMatcher,
            $schemaContainer,
            $urlPrefix
        );

        $this->container->instance(JsonApiContainer::class, $container);
    }

    /**
     * @param Request $request
     * @param $urlNamespace
     * @return string
     */
    private function urlPrefix(Request $request, $urlNamespace)
    {
        return $request->getSchemeAndHttpHost() . $urlNamespace;
    }
}
