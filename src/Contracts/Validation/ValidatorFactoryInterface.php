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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validation;

/**
 * Interface ValidatorFactoryInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ValidatorFactoryInterface
{

    /**
     * Does the resource support client-generated ids?
     *
     * @return bool
     */
    public function supportsClientIds(): bool;

    /**
     * Get a document validator for a create resource request.
     *
     * @param array $document
     *      the JSON API document to validate.
     * @return ValidatorInterface
     */
    public function create(array $document): ValidatorInterface;

    /**
     * Get a document validator for an update resource request.
     *
     * @param object $record
     *      the domain record being updated.
     * @param array $document
     *      the JSON API document to validate.
     * @return ValidatorInterface
     */
    public function update($record, array $document): ValidatorInterface;

    /**
     * Get a validator for a delete resource request.
     *
     * Factories MAY NOT return a validator if the delete request does not
     * need to be validated.
     *
     * @param $record
     * @return ValidatorInterface|null
     */
    public function delete($record): ?ValidatorInterface;

    /**
     * Get a document validator for modifying a relationship.
     *
     * @param object $record
     *      the domain record that is being modified
     * @param string $field
     *      the relationship field being updated.
     * @param array $document
     *      the JSON API document to validate.
     * @return ValidatorInterface
     */
    public function modifyRelationship($record, string $field, array $document): ValidatorInterface;

    /**
     * Get a query validator for searching resources as primary data.
     *
     * I.e. a `GET /posts` request, this method will be invoked on the validators
     * for the `posts` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function fetchManyQuery(array $params): ValidatorInterface;

    /**
     * Get a query validator for the resource when it appears as the primary resource.
     *
     * E.g. a request to `GET /posts/1`, this method will be invoked on the validators
     * for the `posts` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function fetchQuery(array $params): ValidatorInterface;

    /**
     * Get a query validator for the resource when it is being modified.
     *
     * E.g. a request to `POST /posts`, `PATCH /posts/1`, `DELETE /posts/1`, this
     * method will be invoked on the validators for the `posts` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function modifyQuery(array $params): ValidatorInterface;

    /**
     * Get a query validator for the resource when it appears as a related resource.
     *
     * E.g. a `GET /posts/1/comments` request, this method will be
     * invoked on the validators for the `comments` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function fetchRelatedQuery(array $params): ValidatorInterface;

    /**
     * Get a query validator for the resource when it appears in a relationship.
     *
     * E.g. a `GET /posts/1/relationships/comments` request, this method will be
     * invoked on the validators for the `comments` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function fetchRelationshipQuery(array $params): ValidatorInterface;

    /**
     * Get a query validator for the resource when it is appears in a relationship that is being modified.
     *
     * E.g. a `GET /posts/1/relationships/comments`, this method will be invoked
     * on the validators for the `comments` resource.
     *
     * @param array $params
     * @return ValidatorInterface
     */
    public function modifyRelationshipQuery(array $params): ValidatorInterface;

}
