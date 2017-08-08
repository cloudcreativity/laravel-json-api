<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User;

class Comment extends Model
{

    /**
     * For test purposes, our Comment model has client supplied ids.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = [
        'content',
    ];

    /**
     * @return BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
