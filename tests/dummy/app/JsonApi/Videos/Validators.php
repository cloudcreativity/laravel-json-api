<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Videos;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;
use Ramsey\Uuid\Uuid;

class Validators extends AbstractValidators
{

    /**
     * @var array
     */
    protected $allowedIncludePaths = ['uploaded-by'];

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
    ];

    /**
     * @inheritDoc
     */
    protected function rules($record = null): array
    {
        return [
            'id' => 'required|regex:/' . Uuid::VALID_PATTERN . '/',
            'title' => "required|string",
            'description' => "required|string",
            'url' => 'required|url',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [];
    }

}
