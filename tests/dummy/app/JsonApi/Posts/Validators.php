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

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;
use DummyApp\Post;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var array
     */
    protected $queryRules = [
        'filter.title' => 'filled|string',
        'filter.slug' => 'filled|alpha_dash',
        'page.number' => 'integer|min:1',
        'page.size' => 'integer|between:1,50',
    ];

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
     * @inheritdoc
     */
    protected function attributeRules($record = null)
    {
        /** @var Post $record */

        // The JSON API spec says the client does not have to send all attributes for an update request, so
        // if the record already exists we need to include a 'sometimes' before required.
        $required = $record ? 'sometimes|required' : 'required';
        $slugUnique = 'unique:posts,slug';

        if ($record) {
            $slugUnique .= ',' . $record->getKey();
        }

        return [
            'title' => "$required|string|between:1,255",
            'content' => "$required|string|min:1",
            'slug' => "$required|alpha_dash|$slugUnique",
        ];
    }

    /**
     * @inheritdoc
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasOne('author', 'users', false, true);
        $relationships->hasMany('tags', 'tags', false, true);
    }

}
