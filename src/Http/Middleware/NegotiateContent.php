<?php

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NegotiateContent
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * NegotiateContent constructor.
     *
     * @param Factory $factory
     * @param Api $api
     * @param JsonApiRequest $request
     */
    public function __construct(Factory $factory, Api $api, JsonApiRequest $request)
    {
        $this->factory = $factory;
        $this->api = $api;
        $this->jsonApiRequest = $request;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param string|null $default
     *      the default negotiator to use if there is not one for the resource type.
     * @return mixed
     * @throws HttpException
     */
    public function handle($request, \Closure $next, string $default = null)
    {
        $resourceType = $this->resourceType();

        $codec = $this->negotiate(
            $this->negotiator($resourceType, $default),
            $request
        );

        $this->matched($codec);

        return $next($request);
    }

    /**
     * @param ContentNegotiatorInterface $negotiator
     * @param $request
     * @return Codec
     */
    protected function negotiate(ContentNegotiatorInterface $negotiator, $request): Codec
    {
        $codecs = $this->api->getCodecs();

        if ($this->jsonApiRequest->willSeeMany()) {
            return $negotiator->negotiateMany($codecs, $request);
        }

        return $negotiator->negotiate($codecs, $request, $this->jsonApiRequest->getResource());
    }

    /**
     * Get the resource type that will be in the response.
     *
     * @return string
     */
    protected function resourceType(): string
    {
        return $this->jsonApiRequest->getInverseResourceType() ?: $this->jsonApiRequest->getResourceType();
    }

    /**
     * @param string $resourceType
     * @param string|null $default
     * @return ContentNegotiatorInterface
     */
    protected function negotiator(string $resourceType, string $default = null): ContentNegotiatorInterface
    {
        if ($negotiator = $this->getContainer()->getContentNegotiatorByResourceType($resourceType)) {
            return $negotiator;
        }

        if ($default) {
            return $this->getContainer()->getContentNegotiatorByName($default);
        }

        return $this->defaultNegotiator();
    }

    /**
     * Get the default content negotiator.
     *
     * @return ContentNegotiatorInterface
     */
    protected function defaultNegotiator(): ContentNegotiatorInterface
    {
        return $this->factory->createContentNegotiator();
    }

    /**
     * Apply the matched codec.
     *
     * @param Codec $codec
     * @return void
     */
    protected function matched(Codec $codec): void
    {
        $this->jsonApiRequest->setCodec($codec);
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->api->getContainer();
    }
}
