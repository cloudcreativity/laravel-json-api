<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Contracts\Client\ClientInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Resolver\AggregateResolver;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use GuzzleHttp\Client;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
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
     * @var EncodingList
     */
    private $encodings;

    /**
     * @var DecodingList
     */
    private $decodings;

    /**
     * @var array
     * @deprecated 2.0.0
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
     * @var Jobs
     */
    private $jobs;

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
     * @var ErrorRepositoryInterface|null
     * @deprecated 2.0.0
     */
    private $errorRepository;

    /**
     * @var Responses|null
     */
    private $responses;

    /**
     * @var array
     */
    private $providers;

    /**
     * @var string|null
     */
    private $connection;

    /**
     * @var bool
     */
    private $transactions;

    /**
     * Api constructor.
     *
     * @param Factory $factory
     * @param AggregateResolver $resolver
     * @param $apiName
     * @param EncodingList $encodings
     * @param DecodingList $decodings
     * @param Url $url
     * @param Jobs $jobs
     * @param bool $useEloquent
     * @param string|null $supportedExt
     * @param array $errors
     * @param array $providers
     * @param string|null $connection
     * @param bool $transactions
     */
    public function __construct(
        Factory $factory,
        AggregateResolver $resolver,
        $apiName,
        EncodingList $encodings,
        DecodingList $decodings,
        Url $url,
        Jobs $jobs,
        $useEloquent = true,
        $supportedExt = null,
        array $errors = [],
        array $providers = [],
        string $connection = null,
        bool $transactions = true
    ) {
        $this->factory = $factory;
        $this->resolver = $resolver;
        $this->name = $apiName;
        $this->encodings = $encodings;
        $this->decodings = $decodings;
        $this->url = $url;
        $this->jobs = $jobs;
        $this->useEloquent = $useEloquent;
        $this->supportedExt = $supportedExt;
        $this->errors = $errors;
        $this->providers = $providers;
        $this->connection = $connection;
        $this->transactions = $transactions;
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
     * @return Jobs
     */
    public function getJobs()
    {
        return $this->jobs;
    }

    /**
     * @return ContainerInterface
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
     * @deprecated 2.0.0
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
     * @return EncodingList
     */
    public function getEncodings(): EncodingList
    {
        return $this->encodings;
    }

    /**
     * @return DecodingList
     */
    public function getDecodings(): DecodingList
    {
        return $this->decodings;
    }

    /**
     * Get the default API codec.
     *
     * @return Codec
     */
    public function getDefaultCodec(): Codec
    {
        return $this->factory->createCodec(
            $this->getContainer(),
            $this->encodings->find(MediaTypeInterface::JSON_API_MEDIA_TYPE) ?: Encoding::jsonApi(),
            $this->decodings->find(MediaTypeInterface::JSON_API_MEDIA_TYPE)
        );
    }

    /**
     * Get the responses instance for the API.
     *
     * @return Responses
     */
    public function getResponses()
    {
        if (!$this->responses) {
            $this->responses = $this->response();
        }

        return $this->responses;
    }

    /**
     * Get the matched encoder, or a default encoder.
     *
     * @return EncoderInterface
     * @deprecated 2.0.0 use `encoder` to create an encoder.
     */
    public function getEncoder()
    {
        return $this->encoder();
    }

    /**
     * Get the default database connection for the API.
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return $this->connection;
    }

    /**
     * Are database transactions used by default?
     *
     * @return bool
     */
    public function hasTransactions(): bool
    {
        return $this->transactions;
    }

    /**
     * Create an encoder for the API.
     *
     * @param int|EncoderOptions|Encoding $options
     * @param int $depth
     * @return SerializerInterface
     */
    public function encoder($options = 0, $depth = 512)
    {
        if ($options instanceof Encoding) {
            $options = $options->getOptions();
        }

        if (!$options instanceof EncoderOptions) {
            $options = new EncoderOptions($options, $this->getUrl()->toString(), $depth);
        }

        return $this->factory->createEncoder($this->getContainer(), $options);
    }

    /**
     * Create a responses helper for this API.
     *
     * @return Responses
     */
    public function response()
    {
        return $this->factory->createResponseFactory($this);
    }

    /**
     * @param Client|string|array $clientHostOrOptions
     *      Guzzle client, string host or array of Guzzle options
     * @param array $options
     *      Guzzle options, only used if first argument is a string host name.
     * @return ClientInterface
     */
    public function client($clientHostOrOptions = [], array $options = [])
    {
        if (is_array($clientHostOrOptions)) {
            $options = $clientHostOrOptions;
            $options['base_uri'] = isset($options['base_uri']) ?
                $options['base_uri'] : $this->url->getBaseUri();
        }

        if (is_string($clientHostOrOptions)) {
            $options = array_replace($options, [
                'base_uri' => $this->url->withHost($clientHostOrOptions)->getBaseUri(),
            ]);
        }

        $client = ($clientHostOrOptions instanceof Client) ? $clientHostOrOptions : new Client($options);

        return $this->factory->createClient($client, $this->getContainer(), $this->encoder());
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
     * @return ResourceProviders
     */
    public function providers(): ResourceProviders
    {
        return new ResourceProviders(
            $this->factory,
            $this->providers
        );
    }

    /**
     * @return ValidatorFactoryInterface
     * @deprecated 2.0.0
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
     * @param AbstractProvider $provider
     * @return void
     */
    public function register(AbstractProvider $provider)
    {
        $this->resolver->attach($provider->getResolver());
        $this->errors = array_replace($provider->getErrors(), $this->errors);
    }

}
