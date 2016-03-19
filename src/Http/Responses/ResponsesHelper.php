<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Responses;

use CloudCreativity\JsonApi\Contracts\Error\ErrorCollectionInterface;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Responses\ResponsesInterface;

/**
 * Class ResponsesHelper
 * @package CloudCreativity\JsonApi\Laravel
 */
class ResponsesHelper
{

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * @param EnvironmentInterface $environment
     * @param ResponsesInterface $responses
     */
    public function __construct(EnvironmentInterface $environment, ResponsesInterface $responses)
    {
        $this->environment = $environment;
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
     * @param array $links
     * @param mixed|null $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function content($data, array $links = [], $meta = null, $statusCode = Response::HTTP_OK, array $headers = [])
    {
        /** Eloquent collections do not encode properly, so we'll get all just in case it's an Eloquent collection */
        if ($data instanceof Collection || $data instanceof Paginator) {
            $data = $data->all();
        }

        $content = $this
            ->getEncoder()
            ->withLinks($links)
            ->withMeta($meta)
            ->encodeData($data, $this->environment->getParameters());

        return $this->respond($statusCode, $content, $headers);
    }

    /**
     * @param object $resource
     * @param array $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function created($resource, array $links = [], $meta = null, array $headers = [])
    {
        $encoder = $this->getEncoder();

        $content = $encoder
            ->withLinks($links)
            ->withMeta($meta)
            ->encodeData($resource, $this->getEncodingParameters());

        $subHref = $this
            ->environment
            ->getSchemas()
            ->getSchema($resource)
            ->getSelfSubLink($resource)
            ->getSubHref();

        return $this
            ->responses
            ->getCreatedResponse(
                $this->environment->getUrlPrefix() . $subHref,
                $this->environment->getEncoderMediaType(),
                $content,
                $this->environment->getSupportedExtensions(),
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
     * Send a single JSON API error object as a response.
     *
     * The status code will be taken directly from the provided error object using the `ErrorInterface::getStatus()`
     * method. If that method returns a falsey value, then the status code will default to 500.
     *
     * @param ErrorInterface $error
     * @param array $headers
     * @return Response
     */
    public function error(ErrorInterface $error, array $headers = [])
    {
        $statusCode = $error->getStatus() ?: Response::HTTP_INTERNAL_SERVER_ERROR;

        $content = $this
            ->getEncoder()
            ->encodeError($error);

        return $this->respond($statusCode, $content, $headers);
    }

    /**
     * @param ErrorInterface[]|ErrorCollectionInterface $errors
     * @param $statusCode
     *      if not provided, defaults to 500 or the ErrorCollectionInterface status.
     * @param array $headers
     * @return Response
     */
    public function errors($errors, $statusCode = null, array $headers = [])
    {
        if ($errors instanceof ErrorCollectionInterface) {
            $statusCode = is_null($statusCode) ? $errors->getStatus() : $statusCode;
            $errors = $errors->getAll();
        } elseif (is_null($statusCode)) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $content = $this
            ->getEncoder()
            ->encodeErrors($errors);

        return $this->respond($statusCode, $content, $headers);
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
                $this->environment->getEncoderMediaType(),
                $content,
                $this->environment->getSupportedExtensions(),
                $headers
            );
    }

    /**
     * @return EncoderInterface
     */
    private function getEncoder()
    {
        return $this->environment->getEncoder();
    }

    /**
     * @return \Neomerx\JsonApi\Contracts\Parameters\ParametersInterface
     */
    private function getEncodingParameters()
    {
        return $this->environment->getParameters();
    }
}
