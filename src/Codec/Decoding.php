<?php

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;
use CloudCreativity\LaravelJsonApi\Decoder\JsonApiDecoder;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
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
     * @param $key
     * @param $value
     * @return Decoding
     */
    public static function fromArray($key, $value): self
    {
        if (is_numeric($key)) {
            $key = $value;
            $value = new JsonApiDecoder();
        }

        return self::create($key, $value);
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
     * @return JsonApiDecoder
     */
    public function getJsonApiDecoder(): JsonApiDecoder
    {
        if ($this->decoder instanceof JsonApiDecoder) {
            return $this->decoder;
        }

        throw new RuntimeException('Decoder is not a JSON API decoder.');
    }

    /**
     * Will the decoding decode JSON API content?
     *
     * @return bool
     */
    public function isJsonApi(): bool
    {
        return $this->decoder instanceof JsonApiDecoder;
    }

    /**
     * @return bool
     */
    public function isNotJsonApi(): bool
    {
        return $this->isJsonApi();
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
