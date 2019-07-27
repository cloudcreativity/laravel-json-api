<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Document\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Document\Error\Translator;
use CloudCreativity\LaravelJsonApi\Encoder\Neomerx\Document\Errors as NeomerxErrors;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
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
     * @var Translator
     */
    private $translator;

    /**
     * ExceptionParser constructor.
     *
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @inheritdoc
     */
    public function parse(Exception $e): DocumentInterface
    {
        if ($e instanceof JsonApiException) {
            return NeomerxErrors::cast($e);
        }

        $errors = $this->getErrors($e);

        $document = new NeomerxErrors(...$errors);
        $document->setDefaultStatus($this->getDefaultHttpCode($e));

        return $document;
    }

    /**
     * @param Exception $e
     * @return ErrorInterface[]
     */
    protected function getErrors(Exception $e): array
    {
        if ($e instanceof IlluminateValidationException) {
            return $this->getValidationError($e);
        }

        if ($e instanceof AuthenticationException) {
            return [$this->translator->authentication()];
        }

        if ($e instanceof AuthorizationException) {
            return [$this->translator->authorization()];
        }

        if ($e instanceof TokenMismatchException) {
            return [$this->translator->tokenMismatch()];
        }

        if ($e instanceof HttpException) {
            return [$this->getHttpError($e)];
        }

        return [$this->getDefaultError()];
    }

    /**
     * @param IlluminateValidationException $e
     * @return ErrorInterface[]
     */
    protected function getValidationError(IlluminateValidationException $e): array
    {
        return $this->translator->failedValidator($e->validator)->getArrayCopy();
    }

    /**
     * @param HttpException $e
     * @return ErrorInterface
     */
    protected function getHttpError(HttpException $e): ErrorInterface
    {
        $status = $e->getStatusCode();
        $title = $this->getDefaultTitle($status);

        return new Error(null, null, $status, null, $title, $e->getMessage() ?: null);
    }

    /**
     * @return ErrorInterface
     */
    protected function getDefaultError(): ErrorInterface
    {
        return new Error(
            null,
            null,
            $status = Response::HTTP_INTERNAL_SERVER_ERROR,
            null,
            $this->getDefaultTitle($status)
        );
    }

    /**
     * @param Exception $e
     * @return int|null
     */
    protected function getDefaultHttpCode(Exception $e): ?int
    {
        return ($e instanceof HttpExceptionInterface) ?
            $e->getStatusCode() :
            Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * @param string|null $status
     * @return string|null
     */
    protected function getDefaultTitle($status): ?string
    {
        if ($status && isset(Response::$statusTexts[$status])) {
            return Response::$statusTexts[$status];
        }

        return null;
    }

}
