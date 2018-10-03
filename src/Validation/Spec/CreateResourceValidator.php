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

class CreateResourceValidator extends AbstractValidator
{

    /**
     * The expected JSON API type.
     *
     * @var string
     */
    private $expectedType;

    /**
     * CreateResourceValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorTranslator $errorFactory
     * @param object $document
     * @param string $expectedType
     */
    public function __construct(
        StoreInterface $store,
        ErrorTranslator $errorFactory,
        $document,
        $expectedType
    ) {
        if (!is_string($expectedType) || empty($expectedType)) {
            throw new InvalidArgumentException('Expecting type to be a non-empty string.');
        }

        parent::__construct($store, $errorFactory, $document);
        $this->expectedType = $expectedType;
    }

    /**
     * @inheritDoc
     */
    protected function validate()
    {
        /** If the data is not valid, we cannot validate the resource. */
        if (!$this->validateData()) {
            return false;
        }

        /** Validate the resource... */
        $valid = true;

        if (!$this->validateTypeAndId()) {
            $valid = false;
        }

        if ($this->dataHas('attributes') && !$this->validateAttributes()) {
            $valid = false;
        }

        if ($this->dataHas('relationships') && !$this->validateRelationships()) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * Validate that the top-level `data` member is acceptable.
     *
     * @return bool
     */
    protected function validateData()
    {
        if (!property_exists($this->document, 'data')) {
            $this->memberRequired('/', 'data');
            return false;
        }

        $data = $this->document->data;

        if (!is_object($data)) {
            $this->memberNotObject('/', 'data');
            return false;
        }

        return true;
    }

    /**
     * Validate the resource type and id.
     *
     * @return bool
     */
    protected function validateTypeAndId()
    {
        if (!($this->validateType() && $this->validateId())) {
            return false;
        }

        $type = $this->dataGet('type');
        $id = $this->dataGet('id');

        if ($id && !$this->isNotFound($type, $id)) {
            $this->resourceExists($type, $id);
            return false;
        }

        return true;
    }

    /**
     * Validate the resource type.
     *
     * @return bool
     */
    protected function validateType()
    {
        if (!$this->dataHas('type')) {
            $this->memberRequired('/data', 'type');
            return false;
        }

        $value = $this->dataGet('type');

        if (!$this->validateTypeMember($value, '/data')) {
            return false;
        }

        if ($this->expectedType !== $value) {
            $this->resourceTypeNotSupported($value);
            return false;
        }

        return true;
    }

    /**
     * Validate the resource id.
     *
     * @return bool
     */
    protected function validateId()
    {
        if (!$this->dataHas('id')) {
            return true;
        }

        return $this->validateIdMember($this->dataGet('id'), '/data');
    }

    /**
     * Validate the resource attributes.
     *
     * @return bool
     */
    protected function validateAttributes()
    {
        $attrs = $this->dataGet('attributes');

        if (!is_object($attrs)) {
            $this->memberNotObject('/data', 'attributes');
            return false;
        }

        return true;
    }

    /**
     * Validate the resource relationships.
     *
     * @return bool
     */
    protected function validateRelationships()
    {
        $relationships = $this->dataGet('relationships');

        if (!is_object($relationships)) {
            $this->memberNotObject('/data', 'relationships');
            return false;
        }

        $valid = true;

        foreach ($relationships as $field => $relation) {
            if (!$this->validateRelationship($relation, $field)) {
                $valid = false;
            }
        }

        return $valid;
    }

}
