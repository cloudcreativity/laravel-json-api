<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Contracts\Encoder;

use Iterator;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Interface SerializerInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface SerializerInterface extends EncoderInterface
{

    /**
     * @param object|array|Iterator|null $data
     * @param EncodingParametersInterface|null $parameters
     * @return array
     */
    public function serializeData($data, EncodingParametersInterface $parameters = null);

    /**
     * @param object|array|Iterator|null $data
     * @param EncodingParametersInterface|null $parameters
     * @return array
     */
    public function serializeIdentifiers($data, EncodingParametersInterface $parameters = null);

    /**
     * @param ErrorInterface $error
     * @return array
     */
    public function serializeError(ErrorInterface $error);

    /**
     * @param ErrorInterface[]|ErrorCollection $errors
     * @return array
     */
    public function serializeErrors($errors);

    /**
     * @param array|object $meta
     * @return array
     */
    public function serializeMeta($meta);
}
