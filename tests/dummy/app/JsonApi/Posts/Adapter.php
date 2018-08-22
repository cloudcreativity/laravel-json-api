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

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\BelongsTo;
use CloudCreativity\LaravelJsonApi\Eloquent\HasMany;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Post;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $attributes = [
        'published' => 'published_at',
    ];

    /**
     * @var array
     */
    protected $defaultPagination = [
        'number' => 1,
        'size' => 10,
    ];

    /**
     * @var array
     */
    protected $includePaths = [
        'comments.created-by' => 'comments.user',
    ];

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    /**
     * @return BelongsTo
     */
    protected function author()
    {
        return $this->belongsTo();
    }

    /**
     * @return HasMany
     */
    protected function comments()
    {
        return $this->hasMany();
    }

    /**
     * @return HasMany
     */
    protected function tags()
    {
        return $this->hasMany();
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        if ($slug = $filters->get('slug')) {
            $query->where('slug', $slug);
        }

        if ($title = $filters->get('title')) {
            $query->where('title', 'like', $title . '%');
        }

        if ($filters->has('published')) {
            $query->whereNotNull('published_at');
        }
    }

    /**
     * @param Collection $filters
     * @return bool
     */
    protected function isSearchOne(Collection $filters)
    {
        return $filters->has('slug');
    }

}
