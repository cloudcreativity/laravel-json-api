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

namespace CloudCreativity\LaravelJsonApi\Validation\Spec;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\LaravelJsonApi\Validation\ErrorTranslator;

class UpdateResourceValidator extends CreateResourceValidator
{

    /**
     * The expected resource ID.
     *
     * @var string|null
     */
    private $expectedId;

    /**
     * UpdateResourceValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorTranslator $translator
     * @param object $document
     * @param string $expectedType
     * @param string $expectedId
     */
    public function __construct(
        StoreInterface $store,
        ErrorTranslator $translator,
        $document,
        $expectedType,
        $expectedId
    ) {
        if (!is_string($expectedId) || empty($expectedId)) {
            throw new InvalidArgumentException('Expecting id to be null or a non-empty string.');
        }

        parent::__construct($store, $translator, $document, $expectedType);
        $this->expectedId = $expectedId;
    }

    /**
     * @inheritdoc
     */
    protected function validateId()
    {
        if (!$this->dataHas('id')) {
            $this->memberRequired('/data', 'id');
            return false;
        }

        if (!parent::validateId()) {
            return false;
        }

        $id = $this->dataGet('id');

        if ($this->expectedId !== $id) {
            $this->resourceIdNotSupported($id);
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function validateTypeAndId()
    {
        return $this->validateType() && $this->validateId();
    }

}
