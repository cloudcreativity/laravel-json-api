<?php

namespace CloudCreativity\LaravelJsonApi\Validation\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;

class ResourceValidator extends AbstractValidator implements StoreAwareInterface
{

    use StoreAwareTrait;

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
     * @param ErrorFactory $errorFactory
     * @param object $document
     * @param string $expectedType
     * @param string|null $expectedId
     */
    public function __construct(
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

        parent::__construct($errorFactory, $document);
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

        return true;
    }

    /**
     * Validate an identifier object.
     *
     * @param mixed $value
     * @param string $path
     * @param string $member
     * @return bool
     */
    protected function validateIdentifier($value, $path, $member = 'data')
    {
        if (!is_object($value)) {
            $this->memberNotObject($path, $member);
            return false;
        }

        $path .= "/{$member}";
        $valid = true;

        if (!property_exists($value, 'type')) {
            $this->memberRequired($path, 'type');
            $valid = false;
        } else if (!$this->validateTypeMember($value->type, $path)) {
            $valid = false;
        }

        if (!property_exists($value, 'id')) {
            $this->memberRequired($path, 'id');
            $valid = false;
        } else if (!$this->validateIdMember($value->id, $path)) {
            $valid = false;
        }

        return $valid;
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
            if (!$this->validateRelationship($field, $relation)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Validate a resource relationship.
     *
     * @param $field
     * @param $relation
     * @return bool
     */
    protected function validateRelationship($field, $relation)
    {
        if (!is_object($relation)) {
            $this->memberNotObject('/data/relationships', $field);
            return false;
        }

        if (!property_exists($relation, 'data')) {
            $this->memberRequired("/data/relationships/{$field}", 'data');
            return false;
        }

        $data = $relation->data;

        if (is_array($data)) {
            return $this->validateToMany($field, $data);
        }

        return $this->validateToOne($field, $data);
    }


    /**
     * Validate a to-one relation.
     *
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    protected function validateToOne($field, $value)
    {
        if (is_null($value)) {
            return true;
        }

        $path = "/data/relationships/{$field}";

        if (!$this->validateIdentifier($value, $path)) {
            return false;
        }

        if (!$this->getStore()->exists(new ResourceIdentifier($value))) {
            $this->resourceDoesNotExist($path);
            return false;
        }

        return true;
    }

    /**
     * Validate a to-many relation.
     *
     * @param $field
     * @param array $value
     * @return bool
     */
    protected function validateToMany($field, array $value)
    {
        $path = "/data/relationships/{$field}/data";
        $valid = true;

        foreach ($value as $index => $item) {
            if (!$this->validateIdentifier($item, $path, $index)) {
                $valid = false;
            }
        }

        return $valid;
    }

}
