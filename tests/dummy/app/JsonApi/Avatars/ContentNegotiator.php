<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @inheritdoc
     */
    public function negotiate(Codecs $codecs, $request, $record = null): Codec
    {
        $mediaType = optional($record)->media_type;

        $codecs = $codecs->when(!!$mediaType, function (Codecs $codecs) use ($mediaType) {
            return $codecs->custom($mediaType);
        });

        return parent::negotiate($codecs, $request, $record);
    }

}
