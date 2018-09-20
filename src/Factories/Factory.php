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

namespace CloudCreativity\LaravelJsonApi\Factories;

use CloudCreativity\LaravelJsonApi\Api\LinkGenerator;
use CloudCreativity\LaravelJsonApi\Api\ResourceProvider;
use CloudCreativity\LaravelJsonApi\Api\Url;
use CloudCreativity\LaravelJsonApi\Api\UrlGenerator;
use CloudCreativity\LaravelJsonApi\Client\ClientSerializer;
use CloudCreativity\LaravelJsonApi\Client\GuzzleClient;
use CloudCreativity\LaravelJsonApi\Container;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Http\Headers\RestrictiveHeadersChecker;
use CloudCreativity\LaravelJsonApi\Http\Query\ValidationQueryChecker;
use CloudCreativity\LaravelJsonApi\Http\Responses\ErrorResponse;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\LaravelJsonApi\Repositories\ErrorRepository;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use CloudCreativity\LaravelJsonApi\Store\Store;
use CloudCreativity\LaravelJsonApi\Utils\Replacer;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Routing\UrlGenerator as IlluminateUrlGenerator;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as SchemaContainerInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Factories\Factory as BaseFactory;
use Psr\Http\Message\RequestInterface as PsrRequest;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use function CloudCreativity\LaravelJsonApi\http_contains_body;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class Factory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Factory extends BaseFactory implements FactoryInterface
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
     * @inheritdoc
     */
    public function createResolver($rootNamespace, array $resources, $byResource, $withType = true)
    {
        return new NamespaceResolver($rootNamespace, $resources, $byResource, $withType);
    }

    /**
     * @param $name
     * @return ResolverInterface
     */
    public function createCustomResolver($name)
    {
        $resolver = $this->container->make($name);

        if (!$resolver instanceof ResolverInterface) {
            throw new \InvalidArgumentException("Container binding {$name} is not a resolver.");
        }

        return $resolver;
    }

    /**
     * @inheritdoc
     */
    public function createHeadersChecker(CodecMatcherInterface $codecMatcher)
    {
        return new RestrictiveHeadersChecker($codecMatcher, json_api()->getErrors());
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
    public function createErrorResponse($errors, $defaultHttpCode, array $headers = [])
    {
        return new ErrorResponse($errors, $defaultHttpCode, $headers);
    }

    /**
     * @inheritDoc
     */
    public function createDocumentObject(PsrRequest $request, PsrResponse $response = null)
    {
        if (!http_contains_body($request, $response)) {
            return null;
        }

        return new Document(json_decode($response ? $response->getBody() : $request->getBody()));
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
     * @inheritDoc
     */
    public function createConfiguredCodecMatcher(SchemaContainerInterface $schemas, array $codecs, $urlPrefix = null)
    {
        $repository = new CodecMatcherRepository($this);
        $repository->configure($codecs);

        return $repository
            ->registerSchemas($schemas)
            ->registerUrlPrefix($urlPrefix)
            ->getCodecMatcher();
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
    public function createErrorRepository(array $errors)
    {
        $repository = new ErrorRepository($this->createReplacer());
        $repository->configure($errors);

        return $repository;
    }

    /**
     * @inheritDoc
     */
    public function createReplacer()
    {
        return new Replacer();
    }

    /**
     * @inheritDoc
     */
    public function createExtendedQueryChecker(
        $allowUnrecognized = false,
        array $includePaths = null,
        array $fieldSetTypes = null,
        array $sortParameters = null,
        array $pagingParameters = null,
        array $filteringParameters = null,
        QueryValidatorInterface $validator = null
    ) {
        $checker = $this->createQueryChecker(
            $allowUnrecognized,
            $includePaths,
            $fieldSetTypes,
            $sortParameters,
            $pagingParameters,
            $filteringParameters
        );

        return new ValidationQueryChecker($checker, $validator);
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
     * @param ErrorRepositoryInterface $errors
     * @return ValidatorErrorFactory
     */
    protected function createValidatorErrorFactory(ErrorRepositoryInterface $errors)
    {
        return new ValidatorErrorFactory($errors);
    }
}
