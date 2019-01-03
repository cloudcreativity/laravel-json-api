<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\HasOne;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\User;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $with = ['phone'];

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new User(), $paging);
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        if ($name = $filters->get('name')) {
            $query->where('users.name', 'like', "%{$name}%");
        }
    }

    /**
     * @return HasOne
     */
    protected function phone()
    {
        return $this->hasOne();
    }

    /**
     * @inheritdoc
     */
    protected function deserializeAttribute($value, $resourceKey, $record)
    {
        if ('password' === $resourceKey) {
            return $value ? bcrypt($value) : null;
        }

        return parent::deserializeAttribute($value, $resourceKey, $record);
    }

}
