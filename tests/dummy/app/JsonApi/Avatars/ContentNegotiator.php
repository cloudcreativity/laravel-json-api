<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Http\Codec;
use CloudCreativity\LaravelJsonApi\Http\Codecs;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use CloudCreativity\LaravelJsonApi\Http\Decoders;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar $record
     * @return Codecs
     */
    protected function resourceCodecs($record): Codecs
    {
        $mediaType = $record->media_type;

        return $this
            ->defaultCodecs()
            ->optional($mediaType ? Codec::custom($mediaType) : null);
    }

    /**
     * @return Decoders
     */
    protected function decoders(): Decoders
    {
        return $this->defaultDecoders()->when(
            $this->request->isMethod('POST'),
            new FileDecoder()
        );
    }

}
