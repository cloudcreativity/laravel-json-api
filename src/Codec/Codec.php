<?php

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class Codec
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Codec
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Encoding
     */
    private $encoding;

    /**
     * @var Decoding|null
     */
    private $decoding;

    /**
     * Codec constructor.
     *
     * @param FactoryInterface $factory
     * @param ContainerInterface $container
     * @param Encoding $encoding
     * @param Decoding|null $decoding
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $container,
        Encoding $encoding,
        ?Decoding $decoding
    ) {
        $this->factory = $factory;
        $this->container = $container;
        $this->encoding = $encoding;
        $this->decoding = $decoding;
    }

    /**
     * Will the codec encode JSON API content?
     *
     * @return bool
     */
    public function willEncode(): bool
    {
        return $this->encoding->hasOptions();
    }

    /**
     * Will the codec not encode JSON API content?
     *
     * @return bool
     */
    public function willNotEncode(): bool
    {
        return !$this->willEncode();
    }

    /**
     * @return EncoderInterface
     */
    public function getEncoder(): EncoderInterface
    {
        if ($this->willNotEncode()) {
            throw new \RuntimeException('Codec does not support encoding JSON API content.');
        }

        return $this->factory->createEncoder(
            $this->container,
            $this->encoding->getOptions()
        );
    }

    /**
     * @return MediaTypeInterface
     */
    public function getEncodingMediaType(): MediaTypeInterface
    {
        return $this->encoding->getMediaType();
    }

    /**
     * Will the codec decode JSON API content?
     *
     * @return bool
     */
    public function willDecode(): bool
    {
        if (!$this->decoding) {
            return false;
        }

        return $this->decoding->willDecode();
    }

    /**
     * Will the codec not decode JSON API content?
     *
     * @return bool
     */
    public function willNotDecode(): bool
    {
        return !$this->willNotDecode();
    }

    /**
     * @return MediaTypeInterface|null
     */
    public function getDecodingMediaType(): ?MediaTypeInterface
    {
        return $this->decoding ? $this->decoding->getMediaType() : null;
    }

    /**
     * Decode JSON API content from the request.
     *
     * @param $request
     * @return \stdClass|null
     */
    public function decode($request): ?\stdClass
    {
        return $this->willDecode() ? $this->decoding->getDecoder()->decode($request) : null;
    }

    /**
     * Retrieve array input from the request.
     *
     * @param $request
     * @return array
     */
    public function all($request): array
    {
        return $this->decoding ? $this->decoding->getDecoder()->toArray($request) : [];
    }

}
