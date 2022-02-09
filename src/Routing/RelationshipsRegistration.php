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

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

/**
 * Class RelationshipsRegistration
 *
 * @package CloudCreativity\LaravelJsonApi\Routing
 */
final class RelationshipsRegistration implements Arrayable
{

    /**
     * @var array
     */
    private $hasOne;

    /**
     * @var array
     */
    private $hasMany;

    /**
     * RelationshipsRegistration constructor.
     *
     * @param string|array|null $hasOne
     * @param string|array|null $hasMany
     */
    public function __construct($hasOne = [], $hasMany = [])
    {
        $this->hasOne = $this->normalize($hasOne);
        $this->hasMany = $this->normalize($hasMany);
    }

    /**
     * @param string $field
     * @param string|null $inverse
     * @return RelationshipRegistration
     */
    public function hasOne(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasOne[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasOne[$field] = $rel;
    }

    /**
     * @param string $field
     * @param string|null $inverse
     * @return RelationshipRegistration
     */
    public function hasMany(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasMany[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasMany[$field] = $rel;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return [
            'has-one' => collect($this->hasOne)->toArray(),
            'has-many' => collect($this->hasMany)->toArray(),
        ];
    }

    /**
     * @param string|array|null $value
     * @return array
     */
    private function normalize($value): array
    {
        return collect(Arr::wrap($value ?: []))->mapWithKeys(function ($value, $key) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            return [$key => new RelationshipRegistration(Arr::wrap($value))];
        })->all();
    }
}
