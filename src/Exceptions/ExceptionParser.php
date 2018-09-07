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
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection as Errors;
use CloudCreativity\LaravelJsonApi\Http\Responses\ErrorResponse;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
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
     * @var ErrorRepositoryInterface
     */
    private $errors;

    /**
     * @var ErrorIdAllocatorInterface|null
     */
    private $idAllocator;

    /**
     * @var ErrorReporterInterface|null
     */
    private $reporter;

    /**
     * ExceptionParser constructor.
     *
     * @param ErrorRepositoryInterface $errors
     * @param ErrorIdAllocatorInterface|null $idAllocator
     * @param ErrorReporterInterface|null $reporter
     */
    public function __construct(
        ErrorRepositoryInterface $errors,
        ErrorIdAllocatorInterface $idAllocator = null,
        ErrorReporterInterface $reporter = null
    ) {
        $this->errors = $errors;
        $this->idAllocator = $idAllocator;
        $this->reporter = $reporter;
    }

    /**
     * @inheritdoc
     */
    public function parse(Exception $e)
    {
        if ($e instanceof JsonApiException && !$this->errors->exists($this->getErrorKey($e))) {
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

        $response = new ErrorResponse($errors, $httpCode, $this->getHeaders($e));

        if ($this->reporter) {
            $this->reporter->report($response);
        }

        return $response;
    }

    /**
     * @param Exception $e
     * @return ErrorInterface|ErrorInterface[]|ErrorCollection
     */
    protected function getErrors(Exception $e)
    {
        if ($e instanceof IlluminateValidationException) {
            return $this->getValidationError($e);
        }

        if ($error = $this->getError($e)) {
            return $error;
        }

        if ($e instanceof HttpException) {
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
     * @param Exception $e
     * @return MutableErrorInterface|null
     */
    protected function getError(Exception $e)
    {
        $key = $this->getErrorKey($e);

        if (!$this->errors->exists($key)) {
            return null;
        }

        $error = $this->errors->error($key);

        if (!$error->getTitle()) {
            $error->setTitle($this->getDefaultTitle($error->getStatus()));
        }

        return $error;
    }

    /**
     * @param IlluminateValidationException $e
     * @return MutableErrorInterface[]
     */
    protected function getValidationError(IlluminateValidationException $e)
    {
        $errors = [];
        $prototype = $this->getError($e) ?: $this->getDefaultError();

        foreach ($e->validator->getMessageBag()->toArray() as $key => $messages) {
            foreach ($messages as $message) {
                $error = clone $prototype;
                $errors[] = $error->setDetail($message)->setMeta(compact('key'));
            }
        }

        return $errors;
    }

    /**
     * @param HttpException $e
     * @return ErrorInterface
     */
    protected function getHttpError(HttpException $e)
    {
        $status = $e->getStatusCode();
        $title = $this->getDefaultTitle($status);

        return new Error(null, null, $status, null, $title, $e->getMessage() ?: null);
    }

    /**
     * @return MutableErrorInterface
     */
    protected function getDefaultError()
    {
        return $this->errors->error(Exception::class);
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
     * @param string|null $status
     * @return string|null
     */
    protected function getDefaultTitle($status)
    {
        if ($status && isset(Response::$statusTexts[$status])) {
            return Response::$statusTexts[$status];
        }

        return null;
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

}
