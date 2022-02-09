<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Document\Error\Errors;
use Exception;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class JsonApiException extends Exception implements HttpExceptionInterface, Responsable
{

    /**
     * @var Errors
     */
    private $errors;

    /**
     * @var array
     */
    private $headers;

    /**
     * Fluent constructor.
     *
     * @param Errors|Error $errors
     * @param Throwable|null $previous
     * @return static
     */
    public static function make($errors, Throwable $previous = null): self
    {
        return new self($errors, $previous);
    }

    /**
     * JsonApiException constructor.
     *
     * @param Errors|Error $errors
     * @param Throwable|null $previous
     * @param array $headers
     */
    public function __construct($errors, Throwable $previous = null, array $headers = [])
    {
        parent::__construct('JSON API error', 0, $previous);
        $this->errors = Errors::cast($errors);
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->errors->getStatus();
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return Errors
     */
    public function getErrors(): Errors
    {
        return $this->errors
            ->withHeaders($this->headers);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return $this
            ->getErrors()
            ->toResponse($request);
    }

}
