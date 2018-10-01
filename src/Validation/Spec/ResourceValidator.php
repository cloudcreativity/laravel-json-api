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
use CloudCreativity\LaravelJsonApi\Validation\ErrorFactory;

class ResourceValidator extends AbstractValidator
{

    /**
     * The expected JSON API type.
     *
     * @var string
     */
    private $expectedType;

    /**
     * The expected resource ID.
     *
     * @var string|null
     */
    private $expectedId;

    /**
     * ResourceValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorFactory $errorFactory
     * @param object $document
     * @param string $expectedType
     * @param string|null $expectedId
     */
    public function __construct(
        StoreInterface $store,
        ErrorFactory $errorFactory,
        $document,
        $expectedType,
        $expectedId = null
    ) {
        if (!is_string($expectedType) || empty($expectedType)) {
            throw new InvalidArgumentException('Expecting type to be a non-empty string.');
        }

        if (!is_null($expectedId) && (!is_string($expectedId) || empty($expectedId))) {
            throw new InvalidArgumentException('Expecting id to be null or a non-empty string.');
        }

        parent::__construct($store, $errorFactory, $document);
        $this->expectedType = $expectedType;
        $this->expectedId = $expectedId;
    }

    /**
     * Is a resource ID expected in the document?
     *
     * @return bool
     */
    protected function expectsId()
    {
        return !is_null($this->expectedId);
    }

    /**
     * @inheritDoc
     */
    protected function validate()
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

        return $this->validateResourceObject();
    }

    /**
     * Validate the resource object.
     *
     * @return bool
     */
    protected function validateResourceObject()
    {
        $resource = $this->document->data;
        $valid = true;
        $idExists = property_exists($resource, 'id');

        if (!property_exists($resource, 'type')) {
            $this->memberRequired('/data', 'type');
            $valid = false;
        } elseif (!$this->validateType()) {
            $valid = false;
        }

        if ($this->expectsId() && !$idExists) {
            $this->memberRequired('/data', 'id');
            $valid = false;
        }

        if ($idExists && !$this->validateId()) {
            $valid = false;
        }

        if (property_exists($resource, 'attributes') && !$this->validateAttributes()) {
            $valid = false;
        }

        if (property_exists($resource, 'relationships') && !$this->validateRelationships()) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * Validate the resource type.
     *
     * @return bool
     */
    protected function validateType()
    {
        $value = $this->document->data->type;

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
        $value = $this->document->data->id;

        if (!$this->validateIdMember($value, '/data')) {
            return false;
        }

        if ($this->expectedId && $this->expectedId !== $value) {
            $this->resourceIdNotSupported($value);
            return false;
        }

        return true;
    }

    /**
     * Validate the resource attributes.
     *
     * @return bool
     */
    protected function validateAttributes()
    {
        $attrs = $this->document->data->attributes;

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
        $relationships = $this->document->data->relationships;

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
