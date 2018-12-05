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

namespace DummyApp;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{

    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'published_at',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'published_at',
        'deleted_at',
    ];

    /**
     * @return BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * @return MorphToMany
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scope a query for posts that are related to the supplied post.
     *
     * Related posts are those that:
     *
     * - have a tag in common with the provided post; or
     * - are by the same author.
     *
     * @param Builder $query
     * @param Post $post
     * @return Builder
     */
    public function scopeRelated(Builder $query, Post $post)
    {
        return $query->where(function (Builder $q) use ($post) {
            $q->whereHas('tags', function (Builder $t) use ($post) {
                $t->whereIn('tags.id', $post->tags()->pluck('tags.id'));
            })->orWhere('posts.author_id', $post->getKey());
        })->where('posts.id', '<>', $post->getKey());
    }

    /**
     * @return bool
     */
    protected function getPublishedAttribute()
    {
        return isset($this->attributes['published_at']);
    }
}
