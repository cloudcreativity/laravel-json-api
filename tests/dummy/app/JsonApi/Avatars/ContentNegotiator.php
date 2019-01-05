<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use CloudCreativity\LaravelJsonApi\Http\Decoders;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Codecs $defaultCodecs
     * @param Avatar $record
     * @return Codecs
     */
    protected function codecsFor(Codecs $defaultCodecs, $record): Codecs
    {
        return $defaultCodecs->withCustom($record->media_type);
    }

    /**
     * @return Decoders
     */
    protected function decoders(): Decoders
    {
        return parent::decoders()->when(
            $this->request->isMethod('POST'),
            new FileDecoder()
        );
    }

}
