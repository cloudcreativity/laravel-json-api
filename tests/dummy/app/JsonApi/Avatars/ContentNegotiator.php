<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use DummyApp\Avatar;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Api $api
     * @param \Illuminate\Http\Request $request
     * @param Avatar|null $record
     * @return Codecs
     */
    protected function willSeeOne(Api $api, $request, $record = null): Codecs
    {
        $mediaType = optional($record)->media_type ?: 'image/jpeg';

        return parent::willSeeOne($api, $request, $record)
            ->push(Codec::custom($mediaType));
    }
}
