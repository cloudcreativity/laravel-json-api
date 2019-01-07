<?php

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

/**
 * Class Decoding
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Decoding
{

    /**
     * @var MediaTypeInterface
     */
    private $mediaType;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * Create a decoding.
     *
     * @param string|MediaTypeInterface $mediaType
     * @param string|DecoderInterface $decoder
     * @return Decoding
     */
    public static function create($mediaType, $decoder): self
    {
        if (is_string($mediaType)) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        if (!$mediaType instanceof MediaTypeInterface) {
            throw new \InvalidArgumentException('Expecting a media type object or string.');
        }

        if (is_string($decoder)) {
            $decoder = app($decoder);
        }

        if (!$decoder instanceof DecoderInterface) {
            throw new \InvalidArgumentException('Expecting a decoder or decoder service name.');
        }

        return new self($mediaType, $decoder);
    }

    /**
     * Decoding constructor.
     *
     * @param MediaTypeInterface $mediaType
     * @param DecoderInterface $decoder
     */
    public function __construct(MediaTypeInterface $mediaType, DecoderInterface $decoder)
    {
        $this->mediaType = $mediaType;
        $this->decoder = $decoder;
    }

    /**
     * @return MediaTypeInterface
     */
    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * @return DecoderInterface
     */
    public function getDecoder(): DecoderInterface
    {
        return $this->decoder;
    }

    /**
     * Will the decoding decode JSON API content?
     *
     * @return bool
     */
    public function willDecode(): bool
    {
        return $this->decoder->isJsonApi();
    }

    /**
     * @param MediaTypeInterface $mediaType
     * @return bool
     */
    public function equalsTo(MediaTypeInterface $mediaType): bool
    {
        return $this->mediaType->equalsTo($mediaType);
    }

}
