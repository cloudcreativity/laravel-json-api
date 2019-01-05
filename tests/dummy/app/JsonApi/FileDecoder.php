<?php

namespace DummyApp\JsonApi;

use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class FileDecoder implements DecoderInterface
{

    /**
     * @inheritDoc
     */
    public function getMediaType(): MediaTypeInterface
    {
        return MediaType::parse(0, 'multipart/form-data');
    }

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
