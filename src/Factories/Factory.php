<?php

/*
 * Copyright 2021 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Api\AbstractProvider;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\LinkGenerator;
use CloudCreativity\LaravelJsonApi\Api\Url;
use CloudCreativity\LaravelJsonApi\Api\UrlGenerator;
use CloudCreativity\LaravelJsonApi\Client\ClientSerializer;
use CloudCreativity\LaravelJsonApi\Client\GuzzleClient;
use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Container;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Document\Error\Translator as ErrorTranslator;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Encoder\Neomerx\Factory as EncoderFactory;
use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Resolver\ResolverFactory;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use CloudCreativity\LaravelJsonApi\Store\Store;
use CloudCreativity\LaravelJsonApi\Validation;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Routing\UrlGenerator as IlluminateUrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;
use Illuminate\Contracts\Validation\Validator;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as SchemaContainerInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Factories\Factory as BaseFactory;

/**
 * Class Factory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Factory extends BaseFactory
{

    /**
     * @var IlluminateContainer
     */
    protected $container;

    /**
     * Factory constructor.
     *
     * @param IlluminateContainer $container
     */
    public function __construct(IlluminateContainer $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    /**
     * Create a resolver.

     * @param string $apiName
     * @param array $config
     * @return ResolverInterface
     */
    public function createResolver($apiName, array $config)
    {
        $factoryName = isset($config['resolver']) ? $config['resolver'] : ResolverFactory::class;
        $factory = $this->container->make($factoryName);

        if ($factory instanceof ResolverInterface) {
            return $factory;
        }

        if (!is_callable($factory)) {
            throw new RuntimeException("Factory {$factoryName} cannot be invoked.");
        }

        $resolver = $factory($apiName, $config);

        if (!$resolver instanceof ResolverInterface) {
            throw new RuntimeException("Factory {$factoryName} did not create a resolver instance.");
        }

        return $resolver;
    }

    /**
     * @inheritdoc
     */
    public function createExtendedContainer(ResolverInterface $resolver)
    {
        return new Container($this->container, $resolver);
    }

    /**
     * @inheritdoc
     */
    public function createEncoder(SchemaContainerInterface $container, EncoderOptions $encoderOptions = null)
    {
        return $this->createSerializer($container, $encoderOptions);
    }

    /**
     * @inheritDoc
     */
    public function createSerializer(SchemaContainerInterface $container, EncoderOptions $encoderOptions = null)
    {
        $encoder = new Encoder($this, $container, $encoderOptions);
        $encoder->setLogger($this->logger);

        return $encoder;
    }

    /**
     * @inheritDoc
     */
    public function createClient($httpClient, SchemaContainerInterface $container, SerializerInterface $encoder)
    {
        return new GuzzleClient(
            $httpClient,
            $container,
            new ClientSerializer($encoder, $this)
        );
    }

    /**
     * @inheritdoc
     */
    public function createStore(ContainerInterface $container)
    {
        return new Store($container);
    }

    /**
     * @inheritDoc
     */
    public function createPage(
        $data,
        LinkInterface $first = null,
        LinkInterface $previous = null,
        LinkInterface $next = null,
        LinkInterface $last = null,
        $meta = null,
        $metaKey = null
    ) {
        return new Page($data, $first, $previous, $next, $last, $meta, $metaKey);
    }

    /**
     * @param $fqn
     * @return AbstractProvider
     */
    public function createResourceProvider($fqn): AbstractProvider
    {
        return $this->container->make($fqn);
    }

    /**
     * Create a response factory.
     *
     * @param Api $api
     * @return Responses
     */
    public function createResponseFactory(Api $api)
    {
        return new Responses(
            $this->container->make(EncoderFactory::class),
            $api,
            $this->container->make(Route::class),
            $this->container->make('json-api.exceptions')
        );
    }

    /**
     * @param Url $url
     * @return UrlGenerator
     */
    public function createUrlGenerator(Url $url)
    {
        $generator = $this->container->make(IlluminateUrlGenerator::class);

        return new UrlGenerator($generator, $url);
    }

    /**
     * @param UrlGenerator $urls
     * @return LinkGenerator
     */
    public function createLinkGenerator(UrlGenerator $urls)
    {
        $generator = $this->container->make(IlluminateUrlGenerator::class);

        return new LinkGenerator($this, $urls, $generator);
    }

    /**
     * @inheritdoc
     */
    public function createQueryParameters(
        $includePaths = null,
        array $fieldSets = null,
        $sortParameters = null,
        array $pagingParameters = null,
        array $filteringParameters = null,
        array $unrecognizedParams = null
    ) {
        return new EncodingParameters(
            $includePaths,
            $fieldSets,
            $sortParameters,
            $pagingParameters,
            $filteringParameters,
            $unrecognizedParams
        );
    }

    /**
     * Create a validator to check that a resource document complies with the JSON API specification.
     *
     * @param object $document
     * @param string $expectedType
     * @param bool $clientIds
     *      whether client ids are supported.
     * @return Validation\Spec\CreateResourceValidator
     */
    public function createNewResourceDocumentValidator($document, $expectedType, $clientIds)
    {
        $store = $this->container->make(StoreInterface::class);
        $errors = $this->createErrorTranslator();

        return new Validation\Spec\CreateResourceValidator(
            $store,
            $errors,
            $document,
            $expectedType,
            $clientIds
        );
    }

    /**
     * Create a validator to check that a resource document complies with the JSON API specification.
     *
     * @param object $document
     * @param string $expectedType
     * @param string $expectedId
     * @return Validation\Spec\UpdateResourceValidator
     */
    public function createExistingResourceDocumentValidator($document, $expectedType, $expectedId)
    {
        $store = $this->container->make(StoreInterface::class);
        $errors = $this->createErrorTranslator();

        return new Validation\Spec\UpdateResourceValidator(
            $store,
            $errors,
            $document,
            $expectedType,
            $expectedId
        );
    }

    /**
     * Create a validator to check that a relationship document complies with the JSON API specification.
     *
     * @param object $document
     * @return DocumentValidatorInterface
     */
    public function createRelationshipDocumentValidator($document)
    {
        return new Validation\Spec\RelationValidator(
            $this->container->make(StoreInterface::class),
            $this->createErrorTranslator(),
            $document
        );
    }

    /**
     * Create an error translator.
     *
     * @return ErrorTranslator
     */
    public function createErrorTranslator()
    {
        return new ErrorTranslator(
            $this->container->make(Translator::class)
        );
    }

    /**
     * Create a content negotiator.
     *
     * @return ContentNegotiatorInterface
     */
    public function createContentNegotiator()
    {
        return new ContentNegotiator($this);
    }

    /**
     * @param ContainerInterface $container
     * @param Encoding $encoding
     * @param Decoding|null $decoding
     * @return Codec
     * @deprecated 2.0.0 use `Encoder\Neomerx\Factory::createCodec()`
     */
    public function createCodec(ContainerInterface $container, Encoding $encoding, ?Decoding $decoding)
    {
        return new Codec($this, $container, $encoding, $decoding);
    }

    /**
     * Create a Laravel validator that has JSON API error objects.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param \Closure|null $callback
     *       a closure for creating an error, that will be bound to the error translator.
     * @return ValidatorInterface
     */
    public function createValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        \Closure $callback = null
    ): ValidatorInterface
    {
        $translator = $this->createErrorTranslator();

        return new Validation\Validator(
            $this->makeValidator($data, $rules, $messages, $customAttributes),
            $translator,
            $callback
        );
    }

    /**
     * Create a JSON API resource object validator.
     *
     * @param ResourceObject $resource
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    public function createResourceValidator(
        ResourceObject $resource,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        return $this->createValidator(
            $resource->all(),
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) use ($resource) {
                return $this->invalidResource(
                    $resource->pointer($key, '/data'),
                    $detail,
                    $failed
                );
            }
        );
    }

    /**
     * Create a JSON API relationship validator.
     *
     * @param ResourceObject $resource
     *      the resource object, containing only the relationship field.
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    public function createRelationshipValidator(
        ResourceObject $resource,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        return $this->createValidator(
            $resource->all(),
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) use ($resource) {
                return $this->invalidResource(
                    $resource->pointerForRelationship($key, '/data'),
                    $detail,
                    $failed
                );
            }
        );
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    public function createDeleteValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        return $this->createValidator(
            $data,
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail) {
                return $this->resourceCannotBeDeleted($detail);
            }
        );
    }

    /**
     * Create a query validator.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return ValidatorInterface
     */
    public function createQueryValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        return $this->createValidator(
            $data,
            $rules,
            $messages,
            $customAttributes,
            function ($key, $detail, $failed) {
                return $this->invalidQueryParameter($key, $detail, $failed);
            }
        );
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return Validator
     */
    protected function makeValidator(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ) {
        return $this->container
            ->make(ValidatorFactoryContract::class)
            ->make($data, $rules, $messages, $customAttributes);
    }
}
