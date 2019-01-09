<?php

namespace DummyApp\JsonApi\Avatars;

use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Http\ContentNegotiator as BaseContentNegotiator;
use DummyApp\Avatar;
use DummyApp\JsonApi\FileDecoder;

class ContentNegotiator extends BaseContentNegotiator
{

    /**
     * @param Avatar|null $avatar
     * @return EncodingList
     */
    protected function encodingsForOne(?Avatar $avatar): EncodingList
    {
        $mediaType = optional($avatar)->media_type;

        return $this
            ->encodingMediaTypes()
            ->when($this->request->isMethod('GET'), $mediaType);
    }

    /**
     * @param Avatar|null $avatar
     * @return DecodingList
     */
    protected function decodingsForResource(?Avatar $avatar): DecodingList
    {
        $multiPart = Decoding::create('multipart/form-data', new FileDecoder());

        return $this
            ->decodingMediaTypes()
            ->when(is_null($avatar), $multiPart);
    }

}
