<?php

namespace CloudCreativity\LaravelJsonApi\Api;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptMediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Codec
{

    /**
     * @var MediaTypeInterface
     */
    private $mediaType;

    /**
     * @var EncoderOptions|null
     */
    private $options;

    /**
     * Create a codec for the JSON API media type.
     *
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     * @return Codec
     */
    public static function jsonApi(int $options = 0, string $urlPrefix = null, int $depth = 512): self
    {
        return self::encoder(MediaTypeInterface::JSON_API_MEDIA_TYPE, $options, $urlPrefix, $depth);
    }

    /**
     * Create a codec that will encode JSON API content.
     *
     * @param string|MediaTypeInterface $mediaType
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     * @return Codec
     */
    public static function encoder(
        $mediaType,
        int $options = 0,
        string $urlPrefix = null,
        int $depth = 512
    ): self
    {
        if (!$mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        return new self($mediaType, new EncoderOptions($options, $urlPrefix, $depth));
    }

    /**
     * Create a codec that will not encoded JSON API content.
     *
     * @param string|MediaTypeInterface $mediaType
     * @return Codec
     */
    public static function custom($mediaType): self
    {
        if (!$mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaType::parse(0, $mediaType);
        }

        return new self($mediaType, null);
    }

    /**
     * Codec constructor.
     *
     * @param MediaTypeInterface $mediaType
     * @param EncoderOptions|null $options
     *      the encoding options, if the encoding to JSON API content is supported.
     */
    public function __construct(MediaTypeInterface $mediaType, ?EncoderOptions $options)
    {
        $this->mediaType = $mediaType;
        $this->options = $options;
    }

    /**
     * @return MediaTypeInterface
     */
    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * Get the options, if the media type returns JSON API encoded content.
     *
     * @return EncoderOptions
     */
    public function getOptions(): EncoderOptions
    {
        if ($this->willNotEncode()) {
            throw new RuntimeException('Codec does not support encoding to JSON API.');
        }

        return $this->options;
    }

    /**
     * @return bool
     */
    public function willEncode(): bool
    {
        return !is_null($this->options);
    }

    /**
     * @return bool
     */
    public function willNotEncode(): bool
    {
        return !$this->willEncode();
    }

    /**
     * Is the codec for any of the supplied media types?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function is(string ...$mediaTypes): bool
    {
        $mediaTypes = collect($mediaTypes)->map(function ($mediaType, $index) {
            return MediaType::parse($index, $mediaType);
        });

        return $this->any(...$mediaTypes);
    }

    /**
     * @param MediaTypeInterface ...$mediaTypes
     * @return bool
     */
    public function any(MediaTypeInterface ...$mediaTypes): bool
    {
        foreach ($mediaTypes as $mediaType) {
            if ($this->matches($mediaType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the codec match the supplied media type?
     *
     * @param MediaTypeInterface $mediaType
     * @return bool
     */
    public function matches(MediaTypeInterface $mediaType): bool
    {
        return $this->getMediaType()->matchesTo($mediaType);
    }

    /**
     * Is the codec acceptable?
     *
     * @param AcceptMediaTypeInterface $mediaType
     * @return bool
     */
    public function accept(AcceptMediaTypeInterface $mediaType): bool
    {
        // if quality factor 'q' === 0 it means this type is not acceptable (RFC 2616 #3.9)
        if (0 === $mediaType->getQuality()) {
            return false;
        }

        return $this->matches($mediaType);
    }

}
