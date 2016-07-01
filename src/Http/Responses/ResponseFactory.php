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

use CloudCreativity\JsonApi\Exceptions\ErrorCollection as Errors;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PaginatorInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

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
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * ResponseFactory constructor.
     * @param ResponsesInterface $responses
     * @param PaginatorInterface $paginator
     */
    public function __construct(ResponsesInterface $responses, PaginatorInterface $paginator)
    {
        $this->responses = $responses;
        $this->paginator = $paginator;
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
        if ($data instanceof Paginator) {
            $meta = $this->paginator->addMeta($data, $meta);
            $links = $this->paginator->addLinks($data, $links);
        }

        return $this->responses->getContentResponse($data, $statusCode, $links, $meta, $headers);
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
        return $this->responses->getCreatedResponse($resource, $links, $meta, $headers);
    }

    /**
     * @param $data
     * @param array $links
     * @param mixed|null $meta
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public function relationship(
        $data,
        array $links = [],
        $meta = null,
        $statusCode = Response::HTTP_OK,
        array $headers = []
    ) {
        if ($data instanceof Paginator) {
            $meta = $this->paginator->addMeta($data, $meta);
            $links = $this->paginator->addLinks($data, $links);
        }

        return $this->responses->getIdentifiersResponse($data, $statusCode, $links, $meta, $headers);
    }

    /**
     * @param ErrorInterface $error
     * @param int|string|null $statusCode
     * @param array $headers
     * @return Response
     */
    public function error(ErrorInterface $error, $statusCode = null, array $headers = [])
    {
        return $this->errors($error, $statusCode, $headers);
    }

    /**
     * @param ErrorInterface|ErrorInterface[]|ErrorCollection $errors
     * @param int|string|null $statusCode
     * @param array $headers
     * @return Response
     */
    public function errors($errors, $statusCode = null, array $headers = [])
    {
        if (is_null($statusCode)) {
            $statusCode = Errors::cast($errors)->getHttpStatus();
        }

        return $this->responses->getErrorResponse($errors, $statusCode, $headers);
    }

}
