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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ramsey\Uuid\Uuid;

class Tag extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @var array
     */
    protected $visible = [
        'name',
    ];

    /**
     * @inheritdoc
     */
    public static function boot()
    {
        parent::boot();

        self::creating(function (Tag $tag) {
            if (!$tag->uuid) {
                $tag->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * @param $uuid
     * @return Tag|null
     */
    public static function findUuid($uuid)
    {
        return self::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * @return MorphToMany
     */
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    /**
     * @return MorphToMany
     */
    public function videos()
    {
        return $this->morphedByMany(Video::class, 'taggable');
    }
}
