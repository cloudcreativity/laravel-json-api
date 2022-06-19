<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Encoder\EncoderOptions;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Resolver\AggregateResolver;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use GuzzleHttp\Client;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

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
     * @var Url
     */
    private $url;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EncodingList|null
     */
    private $encodings;

    /**
     * @var DecodingList|null
     */
    private $decodings;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var StoreInterface|null
     */
    private $store;

    /**
     * @var Responses|null
     */
    private $responses;

    /**
     * Api constructor.
     *
     * @param Factory $factory
     * @param AggregateResolver $resolver
     * @param string $name
     * @param Url $url
     * @param Config $config
     */
    public function __construct(
        Factory $factory,
        AggregateResolver $resolver,
        string $name,
        Url $url,
        Config $config
    ) {
        $this->factory = $factory;
        $this->resolver = $resolver;
        $this->name = $name;
        $this->url = $url;
        $this->config = $config;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->container = null;
        $this->store = null;
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
        return $this->config->useEloquent();
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
        return Jobs::fromArray($this->config->jobs());
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        if (!$this->container) {
            $this->container = $this->factory->createContainer($this->resolver);
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
     * @return EncodingList
     */
    public function getEncodings(): EncodingList
    {
        if ($this->encodings) {
            return $this->encodings;
        }

        return $this->encodings = EncodingList::fromArray(
            $this->config->encoding(),
            $this->url->toString()
        );
    }

    /**
     * @return DecodingList
     */
    public function getDecodings(): DecodingList
    {
        if ($this->decodings) {
            return $this->decodings;
        }

        return $this->decodings = DecodingList::fromArray($this->config->decoding());
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
            $this->getEncodings()->find(MediaTypeInterface::JSON_API_MEDIA_TYPE) ?: Encoding::jsonApi(),
            $this->getDecodings()->find(MediaTypeInterface::JSON_API_MEDIA_TYPE)
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
     * Get the default database connection for the API.
     *
     * @return string|null
     */
    public function getConnection(): ?string
    {
        return $this->config->dbConnection();
    }

    /**
     * Are database transactions used by default?
     *
     * @return bool
     */
    public function hasTransactions(): bool
    {
        return $this->config->dbTransactions();
    }

    /**
     * @return ExceptionParserInterface
     * @todo add this to config.
     */
    public function exceptions(): ExceptionParserInterface
    {
        return app(ExceptionParserInterface::class);
    }

    /**
     * @return string|null
     */
    public function getModelNamespace(): ?string
    {
        return $this->config->modelNamespace();
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

        return $this->factory
            ->createLaravelEncoder($this->getContainer())
            ->withEncoderOptions($options);
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
            $this->config->providers()
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
    }
}
