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

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Client\ClientInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Resolver\AggregateResolver;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;

/**
 * Class Api
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Api
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var AggregateResolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $codecs;

    /**
     * @var array
     */
    private $errors;

    /**
     * @var bool
     */
    private $useEloquent;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var string|null
     */
    private $supportedExt;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var StoreInterface|null
     */
    private $store;

    /**
     * @var CodecMatcherInterface|null
     */
    private $codecMatcher;

    /**
     * @var ErrorResponseInterface|null
     */
    private $errorRepository;

    /**
     * Definition constructor.
     *
     * @param Factory $factory
     * @param AggregateResolver $resolver
     * @param $apiName
     * @param array $codecs
     * @param Url $url
     * @param bool $useEloquent
     * @param string|null $supportedExt
     * @param array $errors
     */
    public function __construct(
        Factory $factory,
        AggregateResolver $resolver,
        $apiName,
        array $codecs,
        Url $url,
        $useEloquent = true,
        $supportedExt = null,
        array $errors
    ) {
        $this->factory = $factory;
        $this->resolver = $resolver;
        $this->name = $apiName;
        $this->codecs = $codecs;
        $this->url = $url;
        $this->useEloquent = $useEloquent;
        $this->supportedExt = $supportedExt;
        $this->errors = $errors;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->schemas = null;
        $this->store = null;
        $this->codecMatcher = null;
        $this->errorRepository = null;
    }

    /**
     * Get the resolver for the API and packages.
     *
     * @return ResolverInterface
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * Get the API's resolver.
     *
     * @return ResolverInterface
     */
    public function getDefaultResolver()
    {
        return $this->resolver->getDefaultResolver();
    }

    /**
     * @return bool
     */
    public function isByResource()
    {
        $resolver = $this->getDefaultResolver();

        return $resolver instanceof NamespaceResolver;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isEloquent()
    {
        return $this->useEloquent;
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return CodecMatcherInterface
     */
    public function getCodecMatcher()
    {
        if (!$this->codecMatcher) {
            $this->codecMatcher = $this->factory->createConfiguredCodecMatcher(
                $this->getContainer(),
                $this->codecs,
                (string) $this->getUrl()
            );
        }

        return $this->codecMatcher;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainer()
    {
        if (!$this->container) {
            $this->container = $this->factory->createExtendedContainer($this->resolver);
        }

        return $this->container;
    }

    /**
     * @return StoreInterface
     */
    public function getStore()
    {
        if (!$this->store) {
            $this->store = $this->factory->createStore($this->getContainer());
        }

        return $this->store;
    }

    /**
     * @return ErrorRepositoryInterface
     */
    public function getErrors()
    {
        if (!$this->errorRepository) {
            $this->errorRepository = $this->factory->createErrorRepository($this->errors);
        }

        return $this->errorRepository;
    }

    /**
     * @return SupportedExtensionsInterface|null
     */
    public function getSupportedExtensions()
    {
        if (!$this->supportedExt) {
            return null;
        }

        return $this->factory->createSupportedExtensions($this->supportedExt);
    }

    /**
     * Get the matched encoder, or a default encoder.
     *
     * @return EncoderInterface
     */
    public function getEncoder()
    {
        if ($encoder = $this->getCodecMatcher()->getEncoder()) {
            return $encoder;
        }

        $this->getCodecMatcher()->setEncoder($encoder = $this->encoder());

        return $encoder;
    }

    /**
     * @param int $options
     * @param int $depth
     * @return SerializerInterface
     */
    public function encoder($options = 0, $depth = 512)
    {
        $options = new EncoderOptions($options, (string) $this->getUrl(), $depth);

        return $this->factory->createEncoder($this->getContainer(), $options);
    }

    /**
     * Create a responses helper for this API.
     *
     * @param EncodingParametersInterface|null $parameters
     * @param SupportedExtensionsInterface|null $extensions
     * @return Responses
     */
    public function response(
        EncodingParametersInterface $parameters = null,
        SupportedExtensionsInterface $extensions = null
    ) {
        return $this->factory->createResponses(
            $this->getContainer(),
            $this->getErrors(),
            $this->getCodecMatcher(),
            $parameters,
            $extensions ?: $this->getSupportedExtensions(),
            (string) $this->getUrl()
        );
    }

    /**
     * @param $httpClient
     * @return ClientInterface
     */
    public function client($httpClient)
    {
        return $this->factory->createClient($httpClient, $this->getContainer(), $this->encoder());
    }

    /**
     * @return UrlGenerator
     */
    public function url()
    {
        return $this->factory->createUrlGenerator($this->url);
    }

    /**
     * @return LinkGenerator
     */
    public function links()
    {
        return $this->factory->createLinkGenerator($this->url());
    }

    /**
     * @return ValidatorFactoryInterface
     */
    public function validators()
    {
        return $this->factory->createValidatorFactory(
            $this->getErrors(),
            $this->getStore()
        );
    }

    /**
     * Register a resource provider with this API.
     *
     * @param ResourceProvider $provider
     * @return void
     */
    public function register(ResourceProvider $provider)
    {
        $this->resolver->attach($provider->getResolver());
        $this->errors = array_replace($provider->getErrors(), $this->errors);
    }

}
