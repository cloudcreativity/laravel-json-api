<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar $record
     * @return EncodingList
     */
    protected function encodingsForResource($record): EncodingList
    {
        $mediaType = $record->media_type;

        return $this
            ->supportedEncodings()
            ->optional($mediaType ? Encoding::custom($mediaType) : null);
    }

    /**
     * @return DecodingList
     */
    protected function decodingsForCreateResource(): DecodingList
    {
        return $this->supportedDecodings()->when(
            $this->request->isMethod('POST'),
            Decoding::create('multipart/form-data', new FileDecoder())
        );
    }

}
