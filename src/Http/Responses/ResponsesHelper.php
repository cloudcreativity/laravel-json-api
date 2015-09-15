<?php

namespace CloudCreativity\JsonApi\Http\Responses;

use CloudCreativity\JsonApi\Services\EnvironmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Parameters\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;
use RuntimeException;

/**
 * Class ResponsesHelper
 * @package CloudCreativity\JsonApi
 */
class ResponsesHelper
{

    /**
     * @var EnvironmentService
     */
    private $env;

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * @param EnvironmentService $env
     * @param ResponsesInterface $responses
     */
    public function __construct(EnvironmentService $env, ResponsesInterface $responses)
    {
        $this->env = $env;
        $this->responses = $responses;
    }

    /**
     * @param $statusCode
     * @param array $headers
     * @return Response
     */
    public function statusCode($statusCode, array $headers = [])
    {
        return $this->respond($statusCode, null, $headers);
    }

    /**
     * @param array $headers
     * @return Response
     */
    public function noContent(array $headers = [])
    {
        return $this->statusCode(Response::HTTP_NO_CONTENT, $headers);
    }

    /**
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function meta($meta, $statusCode = Response::HTTP_OK, array $headers = [])
    {
        $content = $this
            ->getEncoder()
            ->encodeMeta($meta);

        return $this->respond($statusCode, $content, $headers);
    }

    /**
     * @param mixed $data
     * @param int $statusCode
     * @param array $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function content($data, $statusCode = Response::HTTP_OK, $links = [], $meta = null, array $headers = [])
    {
        if ($data instanceof Collection) {
            $data = $data->all();
        }

        $content = $this
            ->getEncoder()
            ->withLinks($links)
            ->withMeta($meta)
            ->encodeData($data, $this->env->getParameters());

        return $this->respond($statusCode, $content, $headers);
    }

    /**
     * @param object $resource
     * @param array $links
     * @param mixed|null $meta
     * @param array $headers
     * @return mixed
     */
    public function created($resource, $links = [], $meta = null, array $headers = [])
    {
        $encoder = $this->getEncoder();
        $options = $encoder->getEncoderOptions();

        $content = $encoder
            ->withLinks($links)
            ->withMeta($meta)
            ->encodeData($resource, $this->getEncodingParameters());

        $urlPrefix = ($options) ? $options->getUrlPrefix() : null;
        // @todo need to get the schema container so that the location header can be worked out:
        // $subHref = $container->getSchema($resource)->getSelfSubLink($resource)->getSubHref();
        $subHref = null;

        return $this
            ->responses
            ->getCreatedResponse(
                $urlPrefix . $subHref,
                $this->getMediaType(),
                $content,
                $this->getSupportedExtensions(),
                $headers
            );
    }

    /**
     * @param object $resource
     * @param string $relationshipName
     * @param object $related
     * @param array $links
     * @param mixed|null $meta
     * @param mixed|null $selfLinkMeta
     * @param bool $selfLinkTreatAsHref
     * @param mixed|null $relatedLinkMeta
     * @param bool $relatedLinkTreatAsHref
     * @param array $headers
     * @return Response
     */
    public function relationship(
        $resource,
        $relationshipName,
        $related,
        array $links = [],
        $meta = null,
        $selfLinkMeta = null,
        $selfLinkTreatAsHref = false,
        $relatedLinkMeta = null,
        $relatedLinkTreatAsHref = false,
        array $headers = []
    ) {
        $content = $this
            ->getEncoder()
            ->withLinks($links)
            ->withMeta($meta)
            ->withRelationshipSelfLink($resource, $relationshipName, $selfLinkMeta, $selfLinkTreatAsHref)
            ->withRelationshipRelatedLink($resource, $relationshipName, $relatedLinkMeta, $relatedLinkTreatAsHref)
            ->encodeIdentifiers($related, $this->getEncodingParameters());

        return $this->respond(Response::HTTP_OK, $content, $headers);
    }

    /**
     * @param $statusCode
     * @param string|null $content
     * @param array $headers
     * @return Response
     */
    public function respond($statusCode, $content = null, array $headers = [])
    {
        return $this
            ->responses
            ->getResponse(
                (int) $statusCode,
                $this->getMediaType(),
                $content,
                $this->getSupportedExtensions(),
                $headers
            );
    }

    /**
     * @return EncoderInterface
     */
    public function getEncoder()
    {
        $encoder = $this
            ->env
            ->getCodecMatcher()
            ->getEncoder();

        if (!$encoder instanceof EncoderInterface) {
            throw new RuntimeException('No encoder available. Are you in a JSON API route?');
        }

        return $encoder;
    }

    /**
     * @return \Neomerx\JsonApi\Contracts\Parameters\ParametersInterface
     */
    public function getEncodingParameters()
    {
        return $this->env->getParameters();
    }

    /**
     * @return MediaTypeInterface
     */
    private function getMediaType()
    {
        $type = $this
            ->env
            ->getCodecMatcher()
            ->getEncoderRegisteredMatchedType();

        if (!$type instanceof MediaTypeInterface) {
            throw new RuntimeException('No encoder media type available. Are you in a JSON API route?');
        }

        return $type;
    }

    /**
     * @return \Neomerx\JsonApi\Contracts\Parameters\SupportedExtensionsInterface|null
     */
    private function getSupportedExtensions()
    {
        return $this
            ->env
            ->getSupportedExtensions();
    }
}
