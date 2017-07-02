<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * @return BelongsToMany
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
