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
use CloudCreativity\LaravelJsonApi\Contracts\Validation\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Document\Error\Translator as ErrorTranslator;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Class AbstractValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidator implements DocumentValidatorInterface
{

    /**
     * @var object
     */
    protected $document;

    /**
     * @var StoreInterface
     */
    protected $store;

    /**
     * @var ErrorTranslator
     */
    protected $translator;

    /**
     * @var ErrorCollection
     */
    protected $errors;

    /**
     * @var bool|null
     */
    private $valid;

    /**
     * @return bool
     */
    abstract protected function validate(): bool;

    /**
     * AbstractValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorTranslator $translator
     * @param object $document
     */
    public function __construct(StoreInterface $store, ErrorTranslator $translator, $document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Expecting JSON API document to be an object.');
        }

        $this->store = $store;
        $this->document = $document;
        $this->translator = $translator;
        $this->errors = new ErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return bool
     */
    public function passes(): bool
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        return $this->valid = $this->validate();
    }

    /**
     * @inheritDoc
     */
    public function getDocument()
    {
        return clone $this->document;
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): ErrorCollection
    {
        return clone $this->errors;
    }

    /**
     * Validate the value of a type member.
     *
     * @param mixed $value
     * @param string $path
     * @return bool
     */
    protected function validateTypeMember($value, string $path): bool
    {
        if (!is_string($value)) {
            $this->memberNotString($path, 'type');
            return false;
        }

        if (empty($value)) {
            $this->memberEmpty($path, 'type');
            return false;
        }

        if (!$this->store->isType($value)) {
            $this->resourceTypeNotRecognised($value, $path);
            return false;
        }

        return true;
    }

    /**
     * Validate the value of an id member.
     *
     * @param mixed $value
     * @param string $path
     * @return bool
     */
    protected function validateIdMember($value, string $path): bool
    {
        if (!is_string($value)) {
            $this->memberNotString($path, 'id');
            return false;
        }

        if (empty($value)) {
            $this->memberEmpty($path, 'id');
            return false;
        }

        return true;
    }

    /**
     * Validate an identifier object.
     *
     * @param mixed $value
     * @param string $path
     *      the path to the data member in which the identifier is contained.
     * @param int|null $index
     *      the index for the identifier, if in a collection.
     * @return bool
     */
    protected function validateIdentifier($value, string $path, ?int $index = null): bool
    {
        $member = is_int($index) ? (string) $index : 'data';

        if (!is_object($value)) {
            $this->memberNotObject($path, $member);
            return false;
        }

        $dataPath = sprintf('%s/%s', rtrim($path, '/'), $member);
        $valid = true;

        if (!property_exists($value, 'type')) {
            $this->memberRequired($dataPath, 'type');
            $valid = false;
        } else if (!$this->validateTypeMember($value->type, $dataPath)) {
            $valid = false;
        }

        if (!property_exists($value, 'id')) {
            $this->memberRequired($dataPath, 'id');
            $valid = false;
        } else if (!$this->validateIdMember($value->id, $dataPath)) {
            $valid = false;
        }

        /** If it has attributes or relationships, it is a resource object not a resource identifier */
        if (property_exists($value, 'attributes') || property_exists($value, 'relationships')) {
            $this->memberNotIdentifier($path, $member);
            $valid = false;
        }

        return $valid;
    }

    /**
     * Validate a resource relationship.
     *
     * @param mixed $relation
     * @param string|null $field
     * @return bool
     */
    protected function validateRelationship($relation, ?string $field = null): bool
    {
        $path = $field ? '/data/relationships' : '/';
        $member = $field ?: 'data';

        if (!is_object($relation)) {
            $this->memberNotObject($path, $member);
            return false;
        }

        $path = $field ? "{$path}/{$field}" : $path;

        if (!property_exists($relation, 'data')) {
            $this->memberRequired($path, 'data');
            return false;
        }

        $data = $relation->data;

        if (is_array($data)) {
            return $this->validateToMany($data, $field);
        }

        return $this->validateToOne($data, $field);
    }


    /**
     * Validate a to-one relation.
     *
     * @param mixed $value
     * @param string|null $field
     *      the relationship field name.
     * @return bool
     */
    protected function validateToOne($value, ?string $field = null): bool
    {
        if (is_null($value)) {
            return true;
        }

        $dataPath = $field ? "/data/relationships/{$field}" : "/";
        $identifierPath = $field ? "/data/relationships/{$field}" : "/data";

        if (!$this->validateIdentifier($value, $dataPath)) {
            return false;
        }

        if (!$this->store->exists($value->type, $value->id)) {
            $this->resourceDoesNotExist($identifierPath);
            return false;
        }

        return true;
    }

    /**
     * Validate a to-many relation.
     *
     * @param array $value
     * @param string|null $field
     *      the relationship field name.
     * @return bool
     */
    protected function validateToMany(array $value, ?string $field = null): bool
    {
        $path = $field ? "/data/relationships/{$field}/data" : "/data";
        $valid = true;

        foreach ($value as $index => $item) {
            if (!$this->validateIdentifier($item, $path, $index)) {
                $valid = false;
                continue;
            }

            if ($this->isNotFound($item->type, $item->id)) {
                $this->resourceDoesNotExist("{$path}/{$index}");
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Does the key exist in document data object?
     *
     * @param $key
     * @return bool
     */
    protected function dataHas($key): bool
    {
        if (!isset($this->document->data)) {
            return false;
        }

        return property_exists($this->document->data, $key);
    }

    /**
     * Get a value from the document data object use dot notation.
     *
     * @param string|array $key
     * @param mixed $default
     * @return mixed|null
     */
    protected function dataGet($key, $default = null)
    {
        if (!isset($this->document->data)) {
            return $default;
        }

        return data_get($this->document->data, $key, $default);
    }

    /**
     * Check if the resource is not found.
     *
     * @param string $type
     * @param string $id
     * @return bool
     */
    protected function isNotFound(string $type, string $id): bool
    {
        return !$this->store->exists($type, $id);
    }

    /**
     * Add an error for a member that is required.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberRequired(string $path, string $member): void
    {
        $this->errors->add($this->translator->memberRequired($path, $member));
    }

    /**
     * Add an error for a member that must be an object.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotObject(string $path, string $member): void
    {
        $this->errors->add($this->translator->memberNotObject($path, $member));
    }

    /**
     * Add an error for a member that must be an object.
     *
     * @param string $path
     * @param string|null $member
     * @return void
     */
    protected function memberNotIdentifier(string $path, string $member): void
    {
        $this->errors->add($this->translator->memberNotIdentifier($path, $member));
    }

    /**
     * Add errors for when a member has a field that is not allowed.
     *
     * @param string $path
     * @param string $member
     * @param iterable $fields
     */
    protected function memberFieldsNotAllowed(string $path, string $member, iterable $fields): void
    {
        foreach ($fields as $field) {
            $this->errors->add($this->translator->memberFieldNotAllowed($path, $member, $field));
        }
    }

    /**
     * Add an error for a member that must be a string.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotString(string $path, string $member): void
    {
        $this->errors->add($this->translator->memberNotString($path, $member));
    }

    /**
     * Add an error for a member that cannot be an empty value.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberEmpty(string $path, string $member): void
    {
        $this->errors->add($this->translator->memberEmpty($path, $member));
    }

    /**
     * Add an error for when the resource type is not supported by the endpoint.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceTypeNotSupported(string $actual, string $path = '/data'): void
    {
        $this->errors->add($this->translator->resourceTypeNotSupported($actual, $path));
    }

    /**
     * Add an error for when a resource type is not recognised.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceTypeNotRecognised(string $actual, string $path = '/data'): void
    {
        $this->errors->add($this->translator->resourceTypeNotRecognised($actual, $path));
    }

    /**
     * Add an error for when the resource id is not supported by the endpoint.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceIdNotSupported(string $actual, string $path = '/data'): void
    {
        $this->errors->add($this->translator->resourceIdNotSupported($actual, $path));
    }

    /**
     * Add an error for when the resource does not support client-generated ids.
     *
     * @param string $type
     * @param string $path
     * @return void
     */
    protected function resourceDoesNotSupportClientIds(string $type, string $path = '/data'): void
    {
        $this->errors->add($this->translator->resourceDoesNotSupportClientIds($type, $path));
    }

    /**
     * Add an error for when a resource already exists.
     *
     * @param string $type
     * @param string $id
     * @param string $path
     * @return void
     */
    protected function resourceExists(string $type, string $id, string $path = '/data'): void
    {
        $this->errors->add($this->translator->resourceExists($type, $id, $path));
    }

    /**
     * Add an error for when a resource identifier does not exist.
     *
     * @param string $path
     * @return void
     */
    protected function resourceDoesNotExist(string $path): void
    {
        $this->errors->add($this->translator->resourceDoesNotExist($path));
    }

    /**
     * Add errors for when a resource field exists in both the attributes and relationships members.
     *
     * @param iterable $fields
     * @return void
     */
    protected function resourceFieldsExistInAttributesAndRelationships(iterable $fields): void
    {
        foreach ($fields as $field) {
            $this->errors->add(
                $this->translator->resourceFieldExistsInAttributesAndRelationships($field)
            );
        }
    }

}
