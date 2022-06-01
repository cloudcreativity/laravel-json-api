<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Http\Headers\MediaTypeParser;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class DecodingList
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class DecodingList implements IteratorAggregate, Countable
{
    /**
     * @var Decoding[]
     */
    private array $stack;

    /**
     * @param iterable $input
     * @return DecodingList
     */
    public static function fromArray(iterable $input): self
    {
        $list = new self();
        $list->stack = Collection::make($input)->map(
            static fn($value, $key) => Decoding::fromArray($key, $value)
        )->all();

        return $list;
    }

    /**
     * DecodingList constructor.
     *
     * @param Decoding ...$decodings
     */
    public function __construct(Decoding ...$decodings)
    {
        $this->stack = $decodings;
    }

    /**
     * Return a new instance with the supplied decodings added to the beginning of the stack.
     *
     * @param Decoding ...$decodings
     * @return DecodingList
     */
    public function prepend(Decoding ...$decodings): self
    {
        $copy = clone $this;
        array_unshift($copy->stack, ...$decodings);

        return $copy;
    }

    /**
     * Return a new instance with the supplied decodings added to the end of the stack.
     *
     * @param Decoding ...$decodings
     * @return DecodingList
     */
    public function push(Decoding ...$decodings): self
    {
        $copy = new self();
        $copy->stack = Collection::make($this->stack)->merge($decodings)->all();

        return $copy;
    }

    /**
     * Return a new instance with the supplied decodings merged.
     *
     * @param DecodingList $decodings
     * @return DecodingList
     */
    public function merge(DecodingList $decodings): self
    {
        $copy = new self();
        $copy->stack = Collection::make($this->stack)->merge($decodings->stack)->all();

        return $copy;
    }

    /**
     * Push decodings if the truth test evaluates to true.
     *
     * @param bool $test
     * @param Decoding|iterable|\Closure $decodings
     * @return DecodingList
     */
    public function when(bool $test, $decodings): self
    {
        if (!$test) {
            return $this;
        }

        if ($decodings instanceof \Closure) {
            return $decodings($this);
        }

        $decodings = $decodings instanceof Decoding ? [$decodings] : $decodings;

        return $this->push(...$decodings);
    }

    /**
     * Push decodings if the truth test does not evaluate to true.
     *
     * @param bool $test
     * @param $decodings
     * @return DecodingList
     */
    public function unless(bool $test, $decodings): self
    {
        return $this->when(true !== $test, $decodings);
    }

    /**
     * Find a matching decoding by media type.
     *
     * @param string $mediaType
     * @return Decoding|null
     */
    public function find(string $mediaType): ?Decoding
    {
        return $this->equalsTo(MediaTypeParser::make()->parse($mediaType));
    }

    /**
     * Get the decoding that matches the supplied media type.
     *
     * @param MediaTypeInterface $mediaType
     * @return Decoding|null
     */
    public function equalsTo(MediaTypeInterface $mediaType): ?Decoding
    {
        return Collection::make($this->stack)->first(function (Decoding $decoding) use ($mediaType) {
            return $decoding->equalsTo($mediaType);
        });
    }

    /**
     * @param HeaderInterface $header
     * @return Decoding|null
     */
    public function forHeader(HeaderInterface $header): ?Decoding
    {
        foreach ($header->getMediaTypes() as $mediaType) {
            if ($decoding = $this->equalsTo($mediaType)) {
                return $decoding;
            }
        }

        return null;
    }

    /**
     * @return Decoding|null
     */
    public function first(): ?Decoding
    {
        return Collection::make($this->stack)->first();
    }

    /**
     * @return Decoding[]
     */
    public function all(): array
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
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
