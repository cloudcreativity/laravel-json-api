<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Decoders implements \IteratorAggregate, \Countable
{

    /**
     * @var DecoderInterface[]
     */
    private $stack;

    /**
     * Decoders constructor.
     *
     * @param DecoderInterface ...$decoders
     */
    public function __construct(DecoderInterface ...$decoders)
    {
        $this->stack = $decoders;
    }

    /**
     * Return a new instance with the supplied decoders added to the beginning of the stack.
     *
     * @param DecoderInterface ...$decoders
     * @return Decoders
     */
    public function prepend(DecoderInterface ...$decoders): self
    {
        $copy = clone $this;
        array_unshift($copy->stack, ...$decoders);

        return $copy;
    }

    /**
     * Return a new instance with the supplied decoders added to the end of the stack.
     *
     * @param DecoderInterface ...$decoders
     * @return Decoders
     */
    public function push(DecoderInterface ...$decoders): self
    {
        $copy = new self();
        $copy->stack = collect($this->stack)->merge($decoders)->all();

        return $copy;
    }

    /**
     * Return a new instance with the supplied decoders merged.
     *
     * @param Decoders $decoders
     * @return Decoders
     */
    public function merge(Decoders $decoders): self
    {
        $copy = new self();
        $copy->stack = collect($this->stack)->merge($decoders->stack)->all();

        return $copy;
    }

    /**
     * Push decoders if the truth test evaluates to true.
     *
     * @param bool $test
     * @param DecoderInterface|iterable|\Closure $decoders
     * @return Decoders
     */
    public function when(bool $test, $decoders): self
    {
        if (!$test) {
            return $this;
        }

        if ($decoders instanceof \Closure) {
            return $decoders($this);
        }

        $decoders = $decoders instanceof DecoderInterface ? [$decoders] : $decoders;

        return $this->push(...$decoders);
    }

    /**
     * Push decoders if the truth test does not evaluate to true.
     *
     * @param bool $test
     * @param $decoders
     * @return Decoders
     */
    public function unless(bool $test, $decoders): self
    {
        return $this->when(true !== $test, $decoders);
    }

    /**
     * Find a matching decoder by media type.
     *
     * @param string $mediaType
     * @return DecoderInterface|null
     */
    public function find(string $mediaType): ?DecoderInterface
    {
        return $this->equalsTo(MediaType::parse(0, $mediaType));
    }

    /**
     * Get the decoder that matches the supplied media type.
     *
     * @param MediaTypeInterface $mediaType
     * @return DecoderInterface|null
     */
    public function equalsTo(MediaTypeInterface $mediaType): ?DecoderInterface
    {
        return collect($this->stack)->first(function (DecoderInterface $decoder) use ($mediaType) {
            return $decoder->getMediaType()->equalsTo($mediaType);
        });
    }

    /**
     * @param HeaderInterface $header
     * @return DecoderInterface|null
     */
    public function forHeader(HeaderInterface $header): ?DecoderInterface
    {
        foreach ($header->getMediaTypes() as $mediaType) {
            if ($decoder = $this->equalsTo($mediaType)) {
                return $decoder;
            }
        }

        return null;
    }

    /**
     * @return DecoderInterface|null
     */
    public function first(): ?DecoderInterface
    {
        return collect($this->stack)->first();
    }

    /**
     * @return DecoderInterface[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->stack);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

}
