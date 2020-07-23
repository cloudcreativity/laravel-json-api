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

namespace DummyApp\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Rules\HasOne;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'createdAt',
        'updatedAt',
        'content',
    ];

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
        'createdBy',
    ];

    /**
     * @var array
     */
    protected $allowedIncludePaths = [
        'commentable',
        'createdBy',
    ];

    /**
     * @inheritdoc
     */
    protected function rules($record = null): array
    {
        return [
            'content' => "required|string|min:1",
            'commentable' => [
                new HasOne('posts', 'videos'),
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [
            'page.after' => 'filled|integer|min:1',
            'page.before' => 'filled|integer|min:1',
            'page.limit' => 'filled|integer|between:1,50',
            'filter.createdBy' => 'filled|numeric',
        ];
    }


}
