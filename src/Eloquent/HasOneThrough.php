<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Relations;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOneThrough extends BelongsTo
{

    /**
     * @inheritDoc
     */
    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a has-one-through Eloquent relation is not supported.');
    }

    /**
     * @inheritDoc
     */
    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a has-one-through Eloquent relation is not supported.');
    }

    /**
     * @inheritdoc
     */
    protected function acceptRelation($relation)
    {
        return $relation instanceof Relations\HasOneThrough;
    }
}
