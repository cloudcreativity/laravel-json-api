<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\JsonApi\Document\Error;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;

/**
 * Class ResponseFactory
 * @package CloudCreativity\LaravelJsonApi
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
        return $this->responses->getCodeResponse($statusCode, $headers);
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
        return $this->responses->getMetaResponse($meta, $statusCode, $headers);
    }

    /**
     * @param mixed $data
     * @param array $links
     * @param mixed|null $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function content(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = Response::HTTP_OK,
        array $headers = []
    ) {
        /** Collections do not encode properly, so we'll get all just in case it's a collection */
        if ($data instanceof Collection || $data instanceof EloquentCollection) {
            $data = $data->all();
        }

        return $this->responses->getContentResponse($data, $statusCode, $links, $meta, $headers);
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
        return $this->responses->getCreatedResponse($resource, $links, $meta, $headers);
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
        // @todo https://github.com/neomerx/json-api/issues/144
    }

    /**
     * @param ErrorInterface $error
     * @param null $statusCode
     * @param array $headers
     * @return Response
     */
    public function error(ErrorInterface $error, $statusCode = null, array $headers = [])
    {
        return $this->errors($error, $statusCode, $headers);
    }

    /**
     * @param $errors
     * @param null $statusCode
     * @param array $headers
     * @return Response
     */
    public function errors($errors, $statusCode = null, array $headers = [])
    {
        if (is_null($statusCode)) {
            $statusCode = Error::getErrorStatus($errors);
        }

        return $this->responses->getErrorResponse($errors, $statusCode, $headers);
    }

}
