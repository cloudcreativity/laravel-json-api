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

interface HasManyAdapterInterface extends RelationshipAdapterInterface
{

    /**
     * Add data to a domain record's relationship using data from the supplied relationship object.
     *
     * For a has-many relationship, this adds the resource identifiers in the relationship to the domain
     * record's relationship. It is not valid for a has-one relationship.
     *
     * @param mixed $record
     * @param array $relationship
     *      The JSON API relationship object.
     * @param QueryParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function add($record, array $relationship, QueryParametersInterface $parameters);

    /**
     * Remove data from a domain record's relationship using data from the supplied relationship object.
     *
     * For a has-many relationship, this removes the resource identifiers in the relationship from the
     * domain record's relationship. It is not valid for a has-one relationship, as `update()` must
     * be used instead.
     *
     * @param mixed $record
     * @param array $relationship
     *      The JSON API relationship object.
     * @param QueryParametersInterface $parameters
     * @return object
     *      the updated domain record.
     */
    public function remove($record, array $relationship, QueryParametersInterface $parameters);

}
