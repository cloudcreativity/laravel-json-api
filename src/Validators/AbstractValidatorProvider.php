<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use Illuminate\Contracts\Validation\Validator;
use RuntimeException;

/**
 * Class AbstractValidatorProvider
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidatorProvider implements ValidatorProviderInterface
{

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var array
     */
    protected $customAttributes = [];

    /**
     * Get the validation rules for the resource attributes.
     *
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return array
     */
    abstract protected function attributeRules($record = null);

    /**
     * Define the validation rules for the resource relationships.
     *
     * @param RelationshipsValidatorInterface $relationships
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return void
     */
    abstract protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null);

    /**
     * @return DocumentValidatorInterface
     */
    public function createResource()
    {
        $validator = $this->resourceValidator();

        return $this
            ->factory()
            ->resourceDocument($validator);
    }

    /**
     * @param object $record
     * @param string $resourceId
     * @return DocumentValidatorInterface
     */
    public function updateResource($record, $resourceId)
    {
        $validator = $this->resourceValidator($record, $resourceId);

        return $this
            ->factory()
            ->resourceDocument($validator);
    }

    /**
     * @param string $relationshipKey
     * @param $record
     * @return DocumentValidatorInterface
     */
    public function modifyRelationship($relationshipKey, $record)
    {
        $validator = $this
            ->resourceRelationships($record)
            ->get($relationshipKey);

        return $this
            ->factory()
            ->relationshipDocument($validator);
    }

    /**
     * Callback to configure an attributes validator.
     *
     * Child classes can override this method if they need to do custom configuration
     * on the attributes validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the attributes.
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     */
    protected function conditionalAttributes(Validator $validator, $record = null)
    {

    }

    /**
     * @param null $record
     * @param null $resourceId
     * @return ResourceValidatorInterface
     */
    protected function resourceValidator($record = null, $resourceId = null)
    {
        if (empty($this->resourceType)) {
            throw new RuntimeException('The resourceType property must be set on: ' . static::class);
        }

        return $this->factory()->resource(
            $this->resourceType,
            $resourceId,
            $this->resourceAttributes($record),
            $this->resourceRelationships($record)
        );
    }

    /**
     * Get a validator for the resource attributes member.
     *
     * @param object|null $record
     * @return AttributesValidatorInterface
     */
    protected function resourceAttributes($record = null)
    {
        return $this->factory()->attributes(
            $this->attributeRules($record),
            $this->messages,
            $this->customAttributes,
            function (Validator $validator) use ($record) {
                return $this->conditionalAttributes($validator, $record);
            }
        );
    }

    /**
     * Get a validator for the resource relationships member.
     *
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return RelationshipsValidatorInterface
     */
    protected function resourceRelationships($record = null)
    {
        $validator = $this->factory()->relationships();
        $this->relationshipRules($validator, $record);

        return $validator;
    }


    /**
     * @return ValidatorFactoryInterface
     */
    protected function factory()
    {
        return app(ValidatorFactoryInterface::class);
    }
}
