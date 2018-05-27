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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;

/**
 * Class RelationshipValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RelationshipValidator extends AbstractRelationshipValidator
{

    /**
     * @inheritdoc
     */
    public function isValid(
        RelationshipInterface $relationship,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    ) {
        $this->reset();

        /** Check that it is a valid relationship object. */
        if (!$this->validateRelationship($relationship, $key)) {
            return false;
        }

        if ($relationship->isHasOne()) {
            return $this->validateHasOne($relationship, $record, $key, $resource);
        }

        return $this->validateHasMany($relationship, $record, $key, $resource);
    }

}
