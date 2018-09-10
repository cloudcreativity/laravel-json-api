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

namespace DummyApp\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\BelongsTo;
use CloudCreativity\LaravelJsonApi\Pagination\CursorStrategy;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Comment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Adapter extends AbstractAdapter
{

    /**
     * @var array
     */
    protected $attributes = [
        'content',
    ];

    /**
     * @var array
     */
    protected $includePaths = [
        'created-by' => 'user',
    ];

    /**
     * Adapter constructor.
     *
     * @param CursorStrategy $paging
     */
    public function __construct(CursorStrategy $paging)
    {
        parent::__construct(new Comment(), $paging);
    }

    /**
     * @return BelongsTo
     */
    protected function createdBy()
    {
        return $this->belongsTo('user');
    }

    /**
     * @return BelongsTo
     */
    protected function commentable()
    {
        return $this->belongsTo();
    }

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $record = new Comment();
        $record->user()->associate(Auth::user());

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        if ($createdBy = $filters->get('created-by')) {
            $query->where('comments.user_id', $createdBy);
        }
    }

}
