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
use CloudCreativity\JsonApi\Error\ErrorCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;

/**
 * Class ResponsesHelper
 * @package CloudCreativity\JsonApi\Laravel
 */
class ResponseFactory
{

    /**
     * @var ResponsesInterface
     */
    private $responses;

    /**
     * ResponseFactory constructor.
     * @param ResponsesInterface $responses
     */
    public function __construct(ResponsesInterface $responses)
    {
        $this->responses = $responses;
    }

    /**
     * @param $statusCode
     * @param array $headers
     * @return Response
     */
    public function statusCode($statusCode, array $headers = [])
    {
        /** @var Response $response */
        $response = $this->responses->getCodeResponse($statusCode);

        $this->pushHeaders($response, $headers);

        return $response;
    }

    /**
     * @param array $headers
     * @return Response
     */
    public function noContent(array $headers = [])
    {
        /** @var Response $response */
        $response = $this->statusCode(Response::HTTP_NO_CONTENT);

        $this->pushHeaders($response, $headers);

        return $response;
    }

    /**
     * @param mixed $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function meta($meta, $statusCode = Response::HTTP_OK, array $headers = [])
    {
        /** @var Response $response */
        $response = $this->responses->getMetaResponse($meta, $statusCode);

        $this->pushHeaders($response, $headers);

        return $response;
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
        /** Collections do not encode properly, so we'll get all just in case it's a collection */
        if ($data instanceof Collection || $data instanceof EloquentCollection) {
            $data = $data->all();
        }

        $response = $this->responses->getContentResponse($data, $statusCode, $links, $meta);

        $this->pushHeaders($response, $headers);

        return $response;
    }

    /**
     * @param object $resource
     * @param array|null $links
     * @param mixed|null $meta
     * @param array $headers
     * @return Response
     */
    public function created($resource, $links = [], $meta = null, array $headers = [])
    {
        $response = $this->responses->getCreatedResponse($resource, $links, $meta);

        $this->pushHeaders($response, $headers);

        return $response;
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
        // @todo cannot do via the interface currently.
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
        return $this->errors(new ErrorCollection([$error]), null, $headers);
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

        // @todo the problem here is neomerx has not defined an interface for errors.
    }

    /**
     * @param Response $response
     * @param array $headers
     */
    private function pushHeaders(Response $response, array $headers)
    {
        $response->headers->add($headers);
    }
}
