<?php

namespace CloudCreativity\LaravelJsonApi\Tests\JsonApi\Videos;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Hydrator\EloquentHydrator;
use CloudCreativity\LaravelJsonApi\Tests\Models\Video;
use Illuminate\Support\Facades\Auth;

class Hydrator extends EloquentHydrator
{

    /**
     * @var array|null
     */
    protected $attributes = null;

    /**
     * @var array
     */
    protected $relationships = [
        //
    ];

    /**
     * @param ResourceObjectInterface $resource
     * @return Video
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $video = new Video();
        $video->{$video->getKeyName()} = $resource->getId();
        $video->user()->associate(Auth::user());

        return $video;
    }

}
