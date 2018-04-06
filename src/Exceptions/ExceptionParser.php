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

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ErrorIdAllocatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection as Errors;
use CloudCreativity\LaravelJsonApi\Http\Responses\ErrorResponse;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class ExceptionParser
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ExceptionParser implements ExceptionParserInterface
{

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ErrorIdAllocatorInterface|null
     */
    private $idAllocator;

    /**
     * ExceptionParser constructor.
     *
     * @param JsonApiService $service
     * @param FactoryInterface $factory
     * @param ErrorIdAllocatorInterface|null $idAllocator
     */
    public function __construct(
        JsonApiService $service,
        FactoryInterface $factory,
        ErrorIdAllocatorInterface $idAllocator = null
    ) {
        $this->service = $service;
        $this->factory = $factory;
        $this->idAllocator = $idAllocator;
    }

    /**
     * @inheritdoc
     */
    public function parse(Exception $e)
    {
        $repository = $this->getErrorRepository();

        if ($e instanceof JsonApiException && !$repository->exists($this->getErrorKey($e))) {
            $errors = $e->getErrors();
            $httpCode = $e->getHttpCode();
        } else {
            $errors = $this->getErrors($e);
            $httpCode = $this->getDefaultHttpCode($e);
        }

        $errors = Errors::cast($errors);

        /** @var MutableErrorInterface $error */
        foreach ($errors as $error) {
            $this->assignId($error, $e);
        }

        return new ErrorResponse($errors, $httpCode, $this->getHeaders($e));
    }

    /**
     * @param Exception $e
     * @return ErrorInterface|ErrorInterface[]|ErrorCollection
     */
    protected function getErrors(Exception $e)
    {
        $key = $this->getErrorKey($e);
        $repository = $this->getErrorRepository();

        /** If there is an error in the error repository, we'll use that. */
        if ($repository->exists($key)) {
            return $repository->error($key);
        } /** Otherwise if it is a HTTP exception we can create an error for the client */
        elseif ($e instanceof HttpException) {
            return $this->getHttpError($e);
        }

        return $this->getDefaultError();
    }

    /**
     * @param Exception $e
     * @return string
     */
    protected function getErrorKey(Exception $e)
    {
        return get_class($e);
    }

    /**
     * @param HttpException $e
     * @return ErrorInterface
     */
    protected function getHttpError(HttpException $e)
    {
        return new Error(null, null, $e->getStatusCode(), null, null, $e->getMessage() ?: null);
    }

    /**
     * @return ErrorInterface
     */
    protected function getDefaultError()
    {
        return $this->getErrorRepository()->error(Exception::class);
    }

    /**
     * @param Exception $e
     * @return int
     */
    protected function getDefaultHttpCode(Exception $e)
    {
        if ($e instanceof JsonApiException) {
            return $e->getHttpCode();
        }

        return ($e instanceof HttpExceptionInterface) ?
            $e->getStatusCode() :
            Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * @param Exception $e
     * @return array
     */
    protected function getHeaders(Exception $e)
    {
        return [];
    }

    /**
     * @param MutableErrorInterface $error
     * @param Exception $e
     */
    protected function assignId(MutableErrorInterface $error, Exception $e)
    {
        if (!$error->hasId() && $e instanceof ErrorIdAllocatorInterface) {
            $e->assignId($error);
        }

        if (!$error->hasId() && $this->idAllocator) {
            $this->idAllocator->assignId($error);
        }
    }

    /**
     * @return ErrorRepositoryInterface
     */
    protected function getErrorRepository()
    {
        if ($this->service->hasApi()) {
            return $this->service->getApi()->getErrors();
        }

        return $this->factory->createErrorRepository([]);
    }

}
