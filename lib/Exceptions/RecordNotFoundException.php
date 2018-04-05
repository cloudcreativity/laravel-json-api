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

namespace CloudCreativity\JsonApi\Exceptions;

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use Exception;

/**
 * Class RecordNotFoundException
 *
 * @package CloudCreativity\JsonApi
 */
class RecordNotFoundException extends RuntimeException
{

    /**
     * @var ResourceIdentifierInterface $identifier
     */
    private $identifier;

    /**
     * RecordNotFoundException constructor.
     *
     * @param ResourceIdentifierInterface $identifier
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(
        ResourceIdentifierInterface $identifier,
        $code = 0,
        Exception $previous = null
    ) {
        $message = sprintf('Cannot find Record %s:%s', $identifier->getType(), $identifier->getId());
        parent::__construct($message, $code, $previous);
        $this->identifier = $identifier;
    }

    /**
     * @return ResourceIdentifierInterface
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
