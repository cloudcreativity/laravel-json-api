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

namespace CloudCreativity\LaravelJsonApi\Contracts\Adapter;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;

interface RelationshipAdapterInterface
{

    /**
     * Set the field name that the relationship relates to.
     *
     * @param string $field
     * @return $this
     */
    public function withFieldName($field);

    /**
     * Query related resources for the specified domain record.
     *
     * For example, if a client was querying the `comments` relationship of a `posts` resource.
     * This method would be invoked providing the post that is being queried as the `$record` argument.
     *
     * @param mixed $record
     * @param QueryParametersInterface $parameters
     * @return mixed
     */
    public function query($record, QueryParametersInterface $parameters);

    /**
     * Query relationship data for the specified domain record.
     *
     * For example, if a client was querying the `comments` relationship of a `posts` resource.
     * This method would be invoked providing the post that is being queried as the `$record` argument.
     *
     * @param mixed $record
     * @param QueryParametersInterface $parameters
     * @return mixed
     */
    public function relationship($record, QueryParametersInterface $parameters);

    /**
     * Update a domain record's relationship when filling a resource's relationships.
     *
     * For a has-one relationship, this changes the relationship to match the supplied relationship
     * object.
     *
     * For a has-many relationship, this completely replaces every member of the relationship, changing
     * it to match the supplied relationship object.
     *
     * @param mixed $record
     * @param array $relationship
     *      The JSON API relationship object.
     * @param QueryParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function update($record, array $relationship, QueryParametersInterface $parameters);

    /**
     * Replace a domain record's relationship with data from the supplied relationship object.
     *
     * @param mixed $record
     * @param array $relationship
     *      The JSON API relationship object.
     * @param QueryParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function replace($record, array $relationship, QueryParametersInterface $parameters);

}
