<?php

namespace DummyApp\JsonApi;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;

class FileDecoder implements DecoderInterface
{

    /**
     * @inheritdoc
     */
    public function toArray($request): array
    {
        return $request->allFiles();
    }
}
