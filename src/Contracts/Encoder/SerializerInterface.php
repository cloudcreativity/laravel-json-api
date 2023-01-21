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

namespace CloudCreativity\LaravelJsonApi\Contracts\Encoder;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Interface SerializerInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface SerializerInterface extends EncoderInterface
{
    /**
     * @param object|array|iterable|null $data
     * @return array
     */
    public function serializeData($data): array;

    /**
     * @param object|array|iterable|null $data
     * @return array
     */
    public function serializeIdentifiers($data): array;

    /**
     * @param ErrorInterface $error
     * @return array
     */
    public function serializeError(ErrorInterface $error): array;

    /**
     * @param ErrorInterface[]|ErrorCollection $errors
     * @return array
     */
    public function serializeErrors($errors): array;

    /**
     * @param array|object $meta
     * @return array
     */
    public function serializeMeta($meta): array;
}
