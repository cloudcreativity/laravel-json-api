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

namespace CloudCreativity\LaravelJsonApi\Validation\Spec;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Document\Error\Translator as ErrorTranslator;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;

/**
 * Class CreateResourceValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CreateResourceValidator extends AbstractValidator
{

    /**
     * The expected JSON API type.
     *
     * @var string
     */
    private $expectedType;

    /**
     * Whether client ids are supported.
     *
     * @var bool
     */
    private $clientIds;

    /**
     * CreateResourceValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorTranslator $translator
     * @param object $document
     * @param string $expectedType
     * @param bool $clientIds
     *      whether client ids are supported.
     */
    public function __construct(
        StoreInterface $store,
        ErrorTranslator $translator,
        $document,
        string $expectedType,
        bool $clientIds = false
    ) {
        if (empty($expectedType)) {
            throw new InvalidArgumentException('Expecting type to be a non-empty string.');
        }

        parent::__construct($store, $translator, $document);
        $this->expectedType = $expectedType;
        $this->clientIds = $clientIds;
    }

    /**
     * @inheritDoc
     */
    protected function validate(): bool
    {
        /** If the data is not valid, we cannot validate the resource. */
        if (!$this->validateData()) {
            return false;
        }

        return $this->validateResource();
    }

    /**
     * Validate that the top-level `data` member is acceptable.
     *
     * @return bool
     */
    protected function validateData(): bool
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
     * Validate the resource object.
     *
     * @return bool
     */
    protected function validateResource(): bool
    {
        $identifier = $this->validateTypeAndId();
        $attributes = $this->validateAttributes();
        $relationships = $this->validateRelationships();

        if ($attributes && $relationships) {
            return $this->validateAllFields() && $identifier;
        }

        return $identifier && $attributes && $relationships;
    }

    /**
     * Validate the resource type and id.
     *
     * @return bool
     */
    protected function validateTypeAndId(): bool
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
    protected function validateType(): bool
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
    protected function validateId(): bool
    {
        if (!$this->dataHas('id')) {
            return true;
        }

        $valid = $this->validateIdMember($this->dataGet('id'), '/data');

        if (!$this->supportsClientIds()) {
            $valid = false;
            $this->resourceDoesNotSupportClientIds($this->expectedType);
        }

        return $valid;
    }

    /**
     * Validate the resource attributes.
     *
     * @return bool
     */
    protected function validateAttributes(): bool
    {
        if (!$this->dataHas('attributes')) {
            return true;
        }

        $attrs = $this->dataGet('attributes');

        if (!is_object($attrs)) {
            $this->memberNotObject('/data', 'attributes');
            return false;
        }

        $disallowed = collect(['type', 'id'])->filter(function ($field) use ($attrs) {
            return property_exists($attrs, $field);
        });

        $this->memberFieldsNotAllowed('/data', 'attributes', $disallowed);

        return $disallowed->isEmpty();
    }

    /**
     * Validate the resource relationships.
     *
     * @return bool
     */
    protected function validateRelationships(): bool
    {
        if (!$this->dataHas('relationships')) {
            return true;
        }

        $relationships = $this->dataGet('relationships');

        if (!is_object($relationships)) {
            $this->memberNotObject('/data', 'relationships');
            return false;
        }

        $disallowed = collect(['type', 'id'])->filter(function ($field) use ($relationships) {
            return property_exists($relationships, $field);
        });

        $valid = $disallowed->isEmpty();
        $this->memberFieldsNotAllowed('/data', 'relationships', $disallowed);

        foreach ($relationships as $field => $relation) {
            if (!$this->validateRelationship($relation, $field)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Validate the resource's attributes and relationships collectively.
     *
     * @return bool
     */
    protected function validateAllFields(): bool
    {
        $duplicates = collect(
            (array) $this->dataGet('attributes', [])
        )->intersectByKeys(
            (array) $this->dataGet('relationships', [])
        )->keys();

        $this->resourceFieldsExistInAttributesAndRelationships($duplicates);

        return $duplicates->isEmpty();
    }

    /**
     * Are client ids supported?
     *
     * @return bool
     */
    protected function supportsClientIds(): bool
    {
        return $this->clientIds;
    }

}
