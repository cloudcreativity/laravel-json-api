<?php

namespace CloudCreativity\LaravelJsonApi\Api;

use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

class Codecs implements \IteratorAggregate, \Countable
{

    /**
     * @var Codec[]
     */
    private $stack;

    /**
     * @param iterable $config
     * @param string|null $urlPrefix
     * @return Codecs
     */
    public static function create(iterable $config, string $urlPrefix = null): self
    {
        $codecs = collect($config)->mapWithKeys(function ($value, $key) {
            return is_numeric($key) ? [$value => 0] : [$key => $value];
        })->map(function ($options, $mediaType) use ($urlPrefix) {
            return Codec::create($mediaType, $options, $urlPrefix);
        })->values();

        return new self(...$codecs);
    }

    /**
     * Codecs constructor.
     *
     * @param Codec ...$codecs
     */
    public function __construct(Codec ...$codecs)
    {
        $this->stack = $codecs;
    }

    /**
     * Return a new instance with the supplied codecs added to the beginning of the stack.
     *
     * @param Codec ...$codecs
     * @return Codecs
     */
    public function prepend(Codec ...$codecs): self
    {
        $copy = clone $this;
        array_unshift($copy->stack, ...$codecs);

        return $copy;
    }

    /**
     * Return a new instance with the supplied codecs added to the end of the stack.
     *
     * @param Codec ...$codecs
     * @return Codecs
     */
    public function push(Codec ...$codecs): self
    {
        $copy = clone $this;
        $copy->stack = collect($this->stack)->merge($codecs)->all();

        return $copy;
    }

    /**
     * Push codecs if the truth test is met.
     *
     * @param bool $test
     * @param Codec|iterable $codecs
     * @return Codecs
     */
    public function when(bool $test, $codecs): self
    {
        if (!$test) {
            return $this;
        }

        $codecs = $codecs instanceof Codec ? [$codecs] : $codecs;

        return $this->push(...$codecs);
    }

    /**
     * Find a matching codec by media type.
     *
     * @param string $mediaType
     * @return Codec|null
     */
    public function find(string $mediaType): ?Codec
    {
        return $this->matches(MediaType::parse(0, $mediaType));
    }

    /**
     * Get the codec that matches the supplied media type.
     *
     * @param MediaTypeInterface $mediaType
     * @return Codec|null
     */
    public function matches(MediaTypeInterface $mediaType): ?Codec
    {
        return collect($this->stack)->first(function (Codec $codec) use ($mediaType) {
            return $codec->matches($mediaType);
        });
    }

    /**
     * Get the acceptable codec for the supplied Accept header.
     *
     * @param AcceptHeaderInterface $accept
     * @return Codec|null
     */
    public function acceptable(AcceptHeaderInterface $accept): ?Codec
    {
        $mediaTypes = collect($accept->getMediaTypes());

        return collect($this->stack)->first(function (Codec $codec) use ($mediaTypes) {
            return $mediaTypes->contains(function ($mediaType) use ($codec) {
                return $codec->accept($mediaType);
            });
        });
    }

    /**
     * @return Codec|null
     */
    public function first(): ?Codec
    {
        return collect($this->stack)->first();
    }

    /**
     * @return array
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
