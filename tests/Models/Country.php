<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Country extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasManyThrough
     */
    public function posts()
    {
        return $this->hasManyThrough(
            Post::class,
            User::class,
            'country_id',
            'author_id'
        );
    }
}
