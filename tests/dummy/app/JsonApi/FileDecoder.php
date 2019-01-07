<?php

namespace DummyApp\JsonApi;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;

class FileDecoder implements DecoderInterface
{
    /**
     * @inheritDoc
     */
    public function isJsonApi(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function decode($request): \stdClass
    {
        throw new \LogicException('Not supported.');
    }

    /**
     * @inheritdoc
     */
    public function toArray($request): array
    {
        return $request->allFiles();
    }
}
