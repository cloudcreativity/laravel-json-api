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

namespace CloudCreativity\LaravelJsonApi\Factories;

use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Factories\Factory as BaseFactory;
use CloudCreativity\LaravelJsonApi\Api\ResourceProvider;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Schema\Container as SchemaContainer;
use CloudCreativity\LaravelJsonApi\Store\Container as AdapterContainer;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as SchemaContainerInterface;

/**
 * Class Factory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Factory extends BaseFactory
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * Factory constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function createContainer(array $providers = [])
    {
        $container = new SchemaContainer($this->container, $this, $providers);
        $container->setLogger($this->logger);

        return $container;
    }

    /**
     * @inheritdoc
     */
    public function createAdapterContainer(array $adapters)
    {
        $container = new AdapterContainer($this->container);
        $container->registerMany($adapters);

        return $container;
    }

    /**
     * @inheritdoc
     */
    public function createValidatorFactory(ErrorRepositoryInterface $errors, StoreInterface $store)
    {
        /** @var ValidatorFactoryContract $laravelFactory */
        $laravelFactory = $this->container->make(ValidatorFactoryContract::class);

        return new ValidatorFactory($this->createValidatorErrorFactory($errors), $store, $laravelFactory);
    }

    /**
     * @param $fqn
     * @return ResourceProvider
     */
    public function createResourceProvider($fqn)
    {
        $provider = $this->container->make($fqn);

        if (!$provider instanceof ResourceProvider) {
            throw new RuntimeException("Expecting $fqn to resolve to a resource provider instance.");
        }

        return $provider;
    }

    /**
     * @param SchemaContainerInterface $schemas
     * @param ErrorRepositoryInterface $errors
     * @param CodecMatcherInterface|null $codecs
     * @param EncodingParametersInterface|null $parameters
     * @param SupportedExtensionsInterface|null $extensions
     * @param string|null $urlPrefix
     * @return Responses
     */
    public function createResponses(
        SchemaContainerInterface $schemas,
        ErrorRepositoryInterface $errors,
        CodecMatcherInterface $codecs = null,
        EncodingParametersInterface $parameters = null,
        SupportedExtensionsInterface $extensions = null,
        $urlPrefix = null
    ) {
        return new Responses($this, $schemas, $errors, $codecs, $parameters, $extensions, $urlPrefix);
    }

    /**
     * @param ErrorRepositoryInterface $errors
     * @return ValidatorErrorFactory
     */
    protected function createValidatorErrorFactory(ErrorRepositoryInterface $errors)
    {
        return new ValidatorErrorFactory($errors);
    }
}
