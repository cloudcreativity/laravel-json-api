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

use CloudCreativity\JsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Client\GuzzleClient;
use CloudCreativity\JsonApi\Http\Query\ValidationQueryChecker;
use CloudCreativity\JsonApi\Http\Requests\InboundRequest;
use CloudCreativity\JsonApi\Http\Responses\ErrorResponse;
use CloudCreativity\JsonApi\Http\Responses\Response;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\JsonApi\Pagination\Page;
use CloudCreativity\JsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\JsonApi\Repositories\ErrorRepository;
use CloudCreativity\JsonApi\Store\Store;
use CloudCreativity\JsonApi\Utils\Replacer;
use CloudCreativity\LaravelJsonApi\Api\LinkGenerator;
use CloudCreativity\LaravelJsonApi\Api\ResourceProvider;
use CloudCreativity\LaravelJsonApi\Api\Url;
use CloudCreativity\LaravelJsonApi\Api\UrlGenerator;
use CloudCreativity\LaravelJsonApi\Container;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
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
     * @inheritdoc
     */
    public function createInboundRequest(
        $method,
        $resourceType,
        $resourceId = null,
        $relationshipName = null,
        $relationships = false,
        DocumentInterface $document = null,
        EncodingParametersInterface $parameters = null
    ) {
        return new InboundRequest(
            $method,
            $resourceType,
            $resourceId,
            $relationshipName,
            $relationships,
            $document,
            $parameters
        );
    }


    /**
     * @inheritDoc
     */
    public function createResponse(PsrRequest $request, PsrResponse $response)
    {
        return new Response($response, $this->createDocumentObject($request, $response));
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
        return new GuzzleClient($this, $httpClient, $container, $encoder);
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
     * @param ErrorRepositoryInterface $errors
     * @return ValidatorErrorFactory
     */
    protected function createValidatorErrorFactory(ErrorRepositoryInterface $errors)
    {
        return new ValidatorErrorFactory($errors);
    }
}
