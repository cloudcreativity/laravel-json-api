<?php

namespace DummyApp\JsonApi;

use CloudCreativity\LaravelJsonApi\Http\Decoder;

class FileDecoder extends Decoder
{

    /**
     * @inheritdoc
     */
    public function decode($request): ?\stdClass
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function extract($request): array
    {
        return $request->allFiles();
    }
}
