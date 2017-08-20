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

namespace CloudCreativity\LaravelJsonApi\Api;

use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use Illuminate\Contracts\Routing\Registrar;
use IteratorAggregate;

/**
 * Class ResourceProviders
 *
 * @package CloudCreativity\LaravelJsonApi
 * @todo make final as this is not meant to be extended.
 */
class ResourceProviders implements IteratorAggregate
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string[]
     */
    private $providers;

    /**
     * ResourceProviders constructor.
     *
     * @param Factory $factory
     * @param string[] $providers
     */
    public function __construct(Factory $factory, array $providers)
    {
        $this->factory = $factory;
        $this->providers = $providers;
    }

    /**
     * @param Api $api
     * @return void
     */
    public function registerAll(Api $api)
    {
        /** @var ResourceProvider $provider */
        foreach ($this as $provider) {
            $api->register($provider);
        }
    }

    /**
     * @param ApiGroup $api
     * @param Registrar $router
     * @return void
     */
    public function mountAll(ApiGroup $api, Registrar $router)
    {
        /** @var ResourceProvider $provider */
        foreach ($this as $provider) {
            $provider->mount($api, $router);
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        foreach ($this->providers as $provider) {
            yield $provider => $this->factory->createResourceProvider($provider);
        }
    }

}
