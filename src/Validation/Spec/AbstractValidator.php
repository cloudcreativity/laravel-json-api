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
use CloudCreativity\LaravelJsonApi\Contracts\Validation\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Validation\ErrorTranslator;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

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
    protected $errorFactory;

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
    abstract protected function validate();

    /**
     * AbstractValidator constructor.
     *
     * @param StoreInterface $store
     * @param ErrorTranslator $factory
     * @param $document
     */
    public function __construct(StoreInterface $store, ErrorTranslator $factory, $document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Expecting JSON API document to be an object.');
        }

        $this->store = $store;
        $this->document = $document;
        $this->errorFactory = $factory;
        $this->errors = new ErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * @inheritDoc
     */
    public function passes()
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
        return $this->document;
    }

    /**
     * @inheritDoc
     */
    public function getErrors()
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
    protected function validateTypeMember($value, $path)
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
    protected function validateIdMember($value, $path)
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
     * @param string $dataPath
     *      the path to the data member in which the identifier is contained.
     * @param int|null $index
     *      the index for the identifier, if in a collection.
     * @return bool
     */
    protected function validateIdentifier($value, $dataPath, $index = null)
    {
        $member = is_int($index) ? (string) $index : 'data';

        if (!is_object($value)) {
            $this->memberNotObject($dataPath, $member);
            return false;
        }

        $dataPath = sprintf('%s/%s', rtrim($dataPath, '/'), $member);
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

        return $valid;
    }

    /**
     * Validate a resource relationship.
     *
     * @param mixed $relation
     * @param string|null $field
     * @return bool
     */
    protected function validateRelationship($relation, $field = null)
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
    protected function validateToOne($value, $field = null)
    {
        if (is_null($value)) {
            return true;
        }

        $dataPath = $field ? "/data/relationships/{$field}" : "/";
        $identifierPath = $field ? "/data/relationships/{$field}" : "/data";

        if (!$this->validateIdentifier($value, $dataPath)) {
            return false;
        }

        if (!$this->store->exists(new ResourceIdentifier($value))) {
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
    protected function validateToMany(array $value, $field = null)
    {
        $path = $field ? "/data/relationships/{$field}/data" : "/data";
        $valid = true;

        foreach ($value as $index => $item) {
            if (!$this->validateIdentifier($item, $path, $index)) {
                $valid = false;
                continue;
            }

            if (!$this->store->exists(new ResourceIdentifier($item))) {
                $this->resourceDoesNotExist("{$path}/{$index}");
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Add an error for a member that is required.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberRequired($path, $member)
    {
        $this->errors->add($this->errorFactory->memberRequired($path, $member));
    }

    /**
     * Add an error for a member that must be an object.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotObject($path, $member)
    {
        $this->errors->add($this->errorFactory->memberNotObject($path, $member));
    }

    /**
     * Add an error for a member that must be a string.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotString($path, $member)
    {
        $this->errors->add($this->errorFactory->memberNotString($path, $member));
    }

    /**
     * Add an error for a member that cannot be an empty value.
     *
     * @param $path
     * @param $member
     * @return void
     */
    protected function memberEmpty($path, $member)
    {
        $this->errors->add($this->errorFactory->memberEmpty($path, $member));
    }

    /**
     * Add an error for when the resource type is not supported by the endpoint.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceTypeNotSupported($actual, $path = '/data')
    {
        $this->errors->add($this->errorFactory->resourceTypeNotSupported($actual, $path));
    }

    /**
     * Add an error for when a resource type is not recognised.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceTypeNotRecognised($actual, $path = '/data')
    {
        $this->errors->add($this->errorFactory->resourceTypeNotRecognised($actual, $path));
    }

    /**
     * Add an error for when the resource id is not supported by the endpoint.
     *
     * @param string $actual
     * @param string $path
     * @return void
     */
    protected function resourceIdNotSupported($actual, $path = '/data')
    {
        $this->errors->add($this->errorFactory->resourceIdNotSupported($actual, $path));
    }

    /**
     * Add an error for when a resource identifier does not exist.
     *
     * @param $path
     * @return void
     */
    protected function resourceDoesNotExist($path)
    {
        $this->errors->add($this->errorFactory->resourceDoesNotExist($path));
    }

}
