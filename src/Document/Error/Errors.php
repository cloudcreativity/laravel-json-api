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

namespace CloudCreativity\LaravelJsonApi\Document\Error;

use ArrayIterator;
use CloudCreativity\LaravelJsonApi\Contracts\Document\DocumentInterface;
use Illuminate\Http\Response;
use IteratorAggregate;

class Errors implements DocumentInterface, IteratorAggregate
{

    /**
     * @var Error[]
     */
    private $errors;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @param Errors|Error $value
     * @return static
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof Error) {
            return new self($value);
        }

        throw new \UnexpectedValueException('Expecting an errors collection or an error object.');
    }

    /**
     * Errors constructor.
     *
     * @param Error ...$errors
     */
    public function __construct(Error ...$errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @return int|null
     * @see https://jsonapi.org/format/#errors
     */
    public function getStatus(): ?int
    {
        $statuses = collect($this->errors)->filter(function (Error $error) {
            return $error->hasStatus();
        })->map(function (Error $error) {
            return (int) $error->getStatus();
        })->unique();

        if (2 > count($statuses)) {
            return $statuses->first() ?: null;
        }

        $only4xx = $statuses->every(function (int $status) {
            return 400 <= $status && 499 >= $status;
        });

        return $only4xx ? Response::HTTP_BAD_REQUEST : Response::HTTP_INTERNAL_SERVER_ERROR;
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
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'errors' => collect($this->errors)->toArray(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'errors' => collect($this->errors),
        ];
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return json_api()->response()->errors(
            $this->errors,
            null,
            $this->headers
        );
    }

}
