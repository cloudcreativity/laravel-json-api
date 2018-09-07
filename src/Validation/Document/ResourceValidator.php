<?php

namespace CloudCreativity\JsonApi\Validation\Document;

use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;

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
     * @param object $document
     * @param string $expectedType
     * @param string|null $expectedId
     */
    public function __construct($document, $expectedType, $expectedId = null)
    {
        if (!is_string($expectedType) || empty($expectedType)) {
            throw new InvalidArgumentException('Expecting type to be a non-empty string.');
        }

        if (!is_null($expectedId) && (!is_string($expectedId) || empty($expectedId))) {
            throw new InvalidArgumentException('Expecting id to be null or a non-empty string.');
        }

        parent::__construct($document);
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
            $this->dataRequired();
            return false;
        }

        $data = $this->document->data;

        if (!is_object($data)) {
            $this->dataNotObject();
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
            $this->dataTypeRequired();
            $valid = false;
        } elseif (!$this->validateType()) {
            $valid = false;
        }

        if ($this->expectsId() && !$idExists) {
            $this->dataIdRequired();
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
        $type = $this->document->data->type;

        if (!is_string($type)) {
            $this->dataTypeNotString();
            return false;
        }

        if (empty($type)) {
            $this->dataTypeEmpty();
            return false;
        }

        if ($this->expectedType !== $type) {
            $this->addDataTypeNotSupported();
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
        $id = $this->document->data->id;

        if (!is_string($id)) {
            $this->dataIdNotString();
            return false;
        }

        if (empty($id)) {
            $this->dataIdEmpty();
            return false;
        }

        if ($this->expectedId && $this->expectedId !== $id) {
            $this->dataIdNotSupported();
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
            $this->dataAttributesNotObject();
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
            $this->dataRelationshipsNotObject();
            return false;
        }

        return true;
    }

    /**
     * Add a data required error.
     *
     * @return void
     */
    protected function dataRequired()
    {
         $this->errors->addDataError(
             'Required Member',
             "Member 'data' is required.",
             400
         );
    }

    /**
     * Add an object expected error.
     *
     * @return void
     */
    protected function dataNotObject()
    {
        $this->errors->addDataError(
            'Object Expected',
            "Member 'data' must be an object.",
            400
        );
    }

    /**
     * Add a data.type required error.
     *
     * @return void
     */
    protected function dataTypeRequired()
    {
        $this->errors->addDataTypeError(
            'Required Member',
            "Member 'type' is required.",
            400
        );
    }

    /**
     * Add an error for data.type not being a string.
     *
     * @return void
     */
    protected function dataTypeNotString()
    {
        $this->errors->addDataTypeError(
            'String Expected',
            "Member 'type' must be a string.",
            400
        );
    }

    /**
     * Add an error for data.type being an empty string.
     *
     * @return void
     */
    protected function dataTypeEmpty()
    {
        $this->errors->addDataTypeError(
            'Value Expected',
            "Member 'type' must have a value.",
            400
        );
    }

    /**
     * Add an error when the data.type is not the type expected.
     *
     * @return void
     */
    protected function addDataTypeNotSupported()
    {
        $this->errors->addDataTypeError(
            'Not Supported',
            "Resource type '{$this->document->data->type}' is not supported by this endpoint.",
            409
        );
    }

    /**
     * Add a data.id required error.
     *
     * @return void
     */
    protected function dataIdRequired()
    {
        $this->errors->addDataIdError(
            'Required Member',
            "Member 'id' is required.",
            400
        );
    }

    /**
     * Add an error for data.id not being a string.
     *
     * @return void
     */
    protected function dataIdNotString()
    {
        $this->errors->addDataIdError(
            'String Expected',
            "Member 'id' must be a string.",
            400
        );
    }

    /**
     * Add an error for data.id being an empty string.
     *
     * @return void
     */
    protected function dataIdEmpty()
    {
        $this->errors->addDataIdError(
            'Value Expected',
            "Member 'id' must have a value.",
            400
        );
    }

    /**
     * Add an error for data.id not being the expected id.
     *
     * @return void
     */
    protected function dataIdNotSupported()
    {
        $this->errors->addDataIdError(
            'Not Supported',
            "Resource id '{$this->document->data->id}' is not supported by this endpoint.",
            409
        );
    }

    /**
     * Add an error for data.attributes not being an object.
     *
     * @return void
     */
    protected function dataAttributesNotObject()
    {
        $this->errors->addAttributesError(
            'Object Expected',
            "The member 'attributes' is expected to be an object.",
            400
        );
    }

    /**
     * Add an error for data.relationships not being an object.
     *
     * @return void
     */
    protected function dataRelationshipsNotObject()
    {
        $this->errors->addRelationshipsError(
            'Object Expected',
            "The member 'relationships' is expected to be an object",
            400
        );
    }

}
