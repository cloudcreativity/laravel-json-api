<?php

namespace Package;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'article',
        'published_at',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'published_at',
    ];
}
