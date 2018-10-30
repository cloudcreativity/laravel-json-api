<?php

namespace CloudCreativity\LaravelJsonApi\Api;

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
     * @param string $mediaType
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     * @return Codec
     */
    public static function create(
        string $mediaType,
        int $options = 0,
        string $urlPrefix = null,
        int $depth = 512
    ): self
    {
        return new self(
            MediaType::parse(0, $mediaType),
            new EncoderOptions($options, $urlPrefix, $depth)
        );
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
     * @return EncoderOptions|null
     */
    public function getOptions(): ?EncoderOptions
    {
        return $this->options;
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
