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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Http\Headers\MediaTypeParser;
use Countable;
use Generator;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class EncodingList
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class EncodingList implements IteratorAggregate, Countable
{

    /**
     * @var Encoding[]
     */
    private array $stack;

    /**
     * Create encodings from array config.
     *
     * @param iterable $config
     * @param string|null $urlPrefix
     * @return EncodingList
     */
    public static function fromArray(iterable $config, string $urlPrefix = null): self
    {
        $values = Collection::make($config)
            ->map(fn($value, $key) => Encoding::fromArray($key, $value, $urlPrefix))
            ->values();

        return new self(...$values);
    }

    /**
     * Create encodings that will not encode JSON API content.
     *
     * @param string|MediaTypeInterface ...$mediaTypes
     * @return EncodingList
     */
    public static function createCustom(...$mediaTypes): self
    {
        $encodings = new self();
        $encodings->stack = Collection::make($mediaTypes)->map(
            fn($mediaType) => Encoding::custom($mediaType)
        )->all();

        return $encodings;
    }

    /**
     * EncodingList constructor.
     *
     * @param Encoding ...$encodings
     */
    public function __construct(Encoding ...$encodings)
    {
        $this->stack = $encodings;
    }

    /**
     * Return a new instance with the supplied encodings added to the beginning of the stack.
     *
     * @param Encoding ...$encodings
     * @return EncodingList
     */
    public function prepend(Encoding ...$encodings): self
    {
        $copy = clone $this;
        array_unshift($copy->stack, ...$encodings);

        return $copy;
    }

    /**
     * Return a new instance with the supplied encodings added to the end of the stack.
     *
     * @param Encoding ...$encodings
     * @return EncodingList
     */
    public function push(Encoding ...$encodings): self
    {
        $copy = new self();
        $copy->stack = Collection::make($this->stack)->merge($encodings)->all();

        return $copy;
    }

    /**
     * Return a new instance with the supplied encodings merged.
     *
     * @param EncodingList $encodings
     * @return EncodingList
     */
    public function merge(EncodingList $encodings): self
    {
        $copy = new self();
        $copy->stack = Collection::make($this->stack)->merge($encodings->stack)->all();

        return $copy;
    }

    /**
     * Return a new instance with the supplied custom encodings added to the end of the stack.
     *
     * A custom encoding is one that does not encode to JSON API.
     *
     * @param mixed ...$mediaTypes
     * @return EncodingList
     */
    public function withCustom(...$mediaTypes): self
    {
        return $this->merge(self::createCustom(...$mediaTypes));
    }

    /**
     * Push encodings if the truth test evaluates to true.
     *
     * @param bool $test
     * @param Encoding|string|iterable|\Closure|null $encodings
     * @return EncodingList
     */
    public function when(bool $test, $encodings): self
    {
        if (!$test || is_null($encodings)) {
            return $this;
        }

        if ($encodings instanceof \Closure) {
            return $encodings($this);
        }

        if (is_string($encodings)) {
            $encodings = Encoding::custom($encodings);
        }

        $encodings = $encodings instanceof Encoding ? [$encodings] : $encodings;

        return $this->push(...$encodings);
    }

    /**
     * Push encodings if the truth test does not evaluate to true.
     *
     * @param bool $test
     * @param Encoding|string|iterable|\Closure|null $encodings
     * @return EncodingList
     */
    public function unless(bool $test, $encodings): self
    {
        return $this->when(true !== $test, $encodings);
    }

    /**
     * @param Encoding|string|null $encoding
     * @return EncodingList
     */
    public function optional($encoding): self
    {
        if (is_string($encoding)) {
            $encoding = Encoding::custom($encoding);
        }

        return $encoding ? $this->push($encoding) : $this;
    }

    /**
     * Find a matching encoding by media type.
     *
     * @param string $mediaType
     * @return Encoding|null
     */
    public function find(string $mediaType): ?Encoding
    {
        return $this->matchesTo(MediaTypeParser::make()->parse($mediaType));
    }

    /**
     * Get the encoding that matches the supplied media type.
     *
     * @param MediaTypeInterface $mediaType
     * @return Encoding|null
     */
    public function matchesTo(MediaTypeInterface $mediaType): ?Encoding
    {
        return Collection::make($this->stack)->first(function (Encoding $encoding) use ($mediaType) {
            return $encoding->matchesTo($mediaType);
        });
    }

    /**
     * Get the acceptable encoding for the supplied Accept header.
     *
     * @param AcceptHeaderInterface $accept
     * @return Encoding|null
     */
    public function acceptable(AcceptHeaderInterface $accept): ?Encoding
    {
        foreach ($accept->getMediaTypes() as $mediaType) {
            if ($encoding = $this->matchesTo($mediaType)) {
                return $encoding;
            }
        }

        return null;
    }

    /**
     * @return Encoding|null
     */
    public function first(): ?Encoding
    {
        return Collection::make($this->stack)->first();
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
    public function getIterator(): Generator
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
