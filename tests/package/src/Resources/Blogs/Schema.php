<?php

namespace Package\Resources\Blogs;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;

class Schema extends AbstractSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'blogs';

    /**
     * @var array
     */
    protected $attributes = [
        'title',
        'article',
        'published_at',
    ];

}
