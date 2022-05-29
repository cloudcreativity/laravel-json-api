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

namespace CloudCreativity\LaravelJsonApi\Encoder\Neomerx\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class Errors implements DocumentInterface
{

    /**
     * @var ErrorInterface[]
     */
    private $errors;

    /**
     * @var int|null
     */
    private $defaultHttpStatus;

    /**
     * Cast a value to an errors document.
     *
     * @param ErrorInterface|iterable|JsonApiException $value
     * @return Errors
     */
    public static function cast($value): self
    {
        $status = null;

        if ($value instanceof JsonApiException) {
            $status = $value->getHttpCode();
            $value = $value->getErrors();
        }

        if ($value instanceof ErrorInterface) {
            $value = [$value];
        }

        if (!is_iterable($value)) {
            throw new \UnexpectedValueException('Invalid Neomerx error collection.');
        }

        $errors = new self(...collect($value)->values());
        $errors->setDefaultStatus($status);

        return $errors;
    }

    /**
     * Errors constructor.
     *
     * @param ErrorInterface ...$errors
     */
    public function __construct(ErrorInterface ...$errors)
    {
        $this->errors = $errors;
    }

    /**
     * Set the default HTTP status.
     *
     * @param int|null $status
     * @return $this
     */
    public function setDefaultStatus(?int $status): self
    {
        $this->defaultHttpStatus = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return json_api()->encoder()->serializeErrors($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        return json_api()->response()->errors(
            $this->errors,
            $this->defaultHttpStatus
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }


}
