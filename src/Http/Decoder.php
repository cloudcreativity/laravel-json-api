<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class Decoder
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Decoder implements DecoderInterface
{

    /**
     * @inheritdoc
     */
    public function getMediaType(): MediaTypeInterface
    {
        return MediaType::parse(0, MediaTypeInterface::JSON_API_MEDIA_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function decode($request): ?\stdClass
    {
        return json_decode($request->getContent());
    }

    /**
     * @inheritdoc
     */
    public function extract($request): array
    {
        return $request->json()->all();
    }
}
