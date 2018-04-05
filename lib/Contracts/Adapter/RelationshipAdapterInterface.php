<?php
/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Contracts\Adapter;

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

interface RelationshipAdapterInterface
{

    /**
     * Query related resources for the specified domain record.
     *
     * For example, if a client was querying the `comments` relationship of a `posts` resource.
     * This method would be invoked providing the post that is being queried as the `$record` argument.
     *
     * @param object $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters);

    /**
     * Query relationship data for the specified domain record.
     *
     * For example, if a client was querying the `comments` relationship of a `posts` resource.
     * This method would be invoked providing the post that is being queried as the `$record` argument.
     *
     * @param $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function relationship($record, EncodingParametersInterface $parameters);

    /**
     * Update a domain record's relationship with data from the supplied relationship object.
     *
     * For a has-one relationship, this changes the relationship to match the supplied relationship
     * object.
     *
     * For a has-many relationship, this completely replaces every member of the relationship, changing
     * it to match the supplied relationship object.
     *
     * @param object $record
     *      the object to hydrate.
     * @param RelationshipInterface $relationship
     *      the relationship object to use for the hydration.
     * @param EncodingParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters);

    /**
     * Replace a domain record's relationship with data from the supplied relationship object.
     *
     * @param $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters);

}
