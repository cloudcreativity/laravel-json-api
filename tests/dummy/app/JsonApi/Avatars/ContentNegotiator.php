<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use CloudCreativity\LaravelJsonApi\Http\Decoder;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param HeaderInterface $header
     * @param Avatar|null $record
     * @return Decoder
     */
    public function decoder(HeaderInterface $header, $record = null): Decoder
    {
        if (!$record) {
            return new FileDecoder();
        }

        return parent::decoder($header, $record);
    }

    /**
     * @param Codecs $defaultCodecs
     * @param Avatar $record
     * @return Codecs
     */
    protected function codecsFor(Codecs $defaultCodecs, $record): Codecs
    {
        return $defaultCodecs->withCustom($record->media_type);
    }

}
