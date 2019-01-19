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

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
        'name',
        'email',
    ];

    /**
     * @var array
     */
    protected $allowedIncludePaths = [
        'phone',
    ];

    /**
     * @inheritdoc
     */
    public function update($record, array $document): ValidatorInterface
    {
        $validator = parent::update($record, $document);

        $validator->sometimes('password-confirmation', 'required_with:password|same:password', function ($input) {
            return isset($input['password']);
        });

        return $validator;
    }

    /**
     * @inheritDoc
     */
    protected function rules($record = null): array
    {
        $rules = [
            'name' => 'required|string',
            'password' => [
                $record ? 'filled' : 'required',
                'string',
            ],
        ];

        if (!$record) {
            $rules['password-confirmation'] = 'required_with:password|same:password';
        }

        return $rules;
    }

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [
            'filter.name' => 'filled|string',
            'page.number' => 'filled|integer|min:1',
            'page.size' => 'filled|integer|between:1,50',
        ];
    }
}
