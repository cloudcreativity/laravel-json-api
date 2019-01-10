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

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;
use DummyApp\Post;

class Validators extends AbstractValidators
{

    /**
     * @var array
     */
    protected $allowedSortParameters = [
        'created-at',
        'updated-at',
        'title',
        'slug',
    ];

    /**
     * @var array
     */
    protected $allowedFilteringParameters = [
        'id',
        'title',
        'slug',
        'published',
    ];

    /**
     * @var array
     */
    protected $allowedIncludePaths = [
        'author',
        'comments',
        'comments.created-by',
        'tags',
    ];

    /**
     * @var array
     */
    protected $allowedFieldSets = [
        'posts' => ['title', 'content', 'slug', 'author', 'tags'],
    ];

    /**
     * @var array
     */
    protected $allowedPagingParameters = [
        'number',
        'size',
        // these are used as custom keys in a test...
        'page',
        'limit',
    ];

    /**
     * @var array
     */
    protected $deleteMessages = [
        'no_comments.accepted' => 'Cannot delete a post with comments.',
    ];

    /**
     * @param Post|null $record
     * @return array|mixed
     */
    protected function rules($record = null): array
    {
        $slugUnique = 'unique:posts,slug';

        if ($record) {
            $slugUnique .= ',' . $record->getKey();
        }

        return [
            'title' => "required|string|between:1,255",
            'content' => "required|string|min:1",
            'slug' => "required|alpha_dash|$slugUnique",
            'published' => [
                'nullable',
                new DateTimeIso8601()
            ],
            'author.type' => 'in:users',
            'tags.*.type' => 'in:tags',
        ];
    }

    /**
     * @param Post $record
     * @return array|null
     */
    protected function deleteRules($record): ?array
    {
        return [
            'no_comments' => 'accepted',
        ];
    }

    /**
     * @param Post $record
     * @return array
     */
    protected function dataForDelete($record): array
    {
        return [
            'no_comments' => $record->comments()->doesntExist(),
        ];
    }

    /**
     * @inheritDoc
     */
    protected function queryRules(): array
    {
        return [
            'filter.title' => 'filled|string',
            'filter.slug' => 'filled|alpha_dash',
            'filter.published' => 'boolean',
            'page.number' => 'integer|min:1',
            'page.size' => 'integer|between:1,50',
        ];
    }

}
