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

namespace CloudCreativity\LaravelJsonApi\Document\Error;

use CloudCreativity\LaravelJsonApi\Document\Concerns\HasMeta;
use CloudCreativity\LaravelJsonApi\Document\Link\Link;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use UnexpectedValueException;
use function array_filter;
use function is_array;
use function is_int;
use function is_null;
use function is_string;

class Error implements Arrayable, \JsonSerializable
{

    private const CODE = 'code';
    private const DETAIL = 'detail';
    private const ID = 'id';
    private const LINKS = 'links';
    private const LINKS_ABOUT = 'about';
    private const META = 'meta';
    private const SOURCE = 'source';
    private const SOURCE_POINTER = 'pointer';
    private const SOURCE_PARAMETER = 'parameter';
    private const STATUS = 'status';
    private const TITLE = 'title';

    use HasMeta;

    /**
     * @var string|int|null
     */
    private $id;

    /**
     * @var string|null
     */
    private $status;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string|null
     */
    private $detail;

    /**
     * @var array
     */
    private $source;

    /**
     * @var array|null
     */
    private $links;

    /**
     * Cast a value to an error.
     *
     * @param Error|array $value
     * @return Error
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            return self::fromArray($value);
        }

        throw new UnexpectedValueException('Expecting an error object or an array.');
    }

    /**
     * Create an error from an array.
     *
     * @param array $input
     * @return Error
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input[self::ID] ?? null,
            $input[self::STATUS] ?? null,
            $input[self::CODE] ?? null,
            $input[self::TITLE] ?? null,
            $input[self::DETAIL] ?? null,
            $input[self::SOURCE] ?? null,
            $input[self::LINKS] ?? null,
            $input[self::META] ?? null
        );
    }

    /**
     * Error constructor.
     *
     * @param string|int|null $id
     * @param string|int|null $status
     * @param string|null $code
     * @param string|null $title
     * @param string|null $detail
     * @param iterable|null $source
     * @param iterable|null $links
     * @param iterable|null $meta
     */
    public function __construct(
        $id = null,
        $status = null,
        string $code = null,
        string $title = null,
        string $detail = null,
        iterable $source = null,
        iterable $links = null,
        iterable $meta = null
    )
    {
        $this->setId($id);
        $this->setStatus($status);
        $this->setCode($code);
        $this->setTitle($title);
        $this->setDetail($detail);
        $this->setSource($source);
        $this->setLinks($links);
        $this->setMeta($meta);
    }

    /**
     * The unique identifier for this particular occurrence of the problem.
     *
     * @return string|int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set a unique identifier for this particular occurrence of the problem.
     *
     * @param string|int|null $id
     * @return $this
     */
    public function setId($id): self
    {
        if (!is_string($id) && !is_int($id) && !is_null($id)) {
            throw new InvalidArgumentException('Expecting a string, integer or null id.');
        }

        $this->id = $id ?: null;

        return $this;
    }

    /**
     * The HTTP status code applicable to this problem, expressed as a string value.
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Set the HTTP status code applicable to this problem.
     *
     * @param string|int|null $status
     * @return $this
     */
    public function setStatus($status): self
    {
        if (is_int($status)) {
            $status = (string) $status;
        }

        if (!is_string($status) && !is_null($status)) {
            throw new InvalidArgumentException('Expecting an integer, string or null status.');
        }

        $this->status = $status ?: null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasStatus(): bool
    {
        return !is_null($this->status);
    }

    /**
     * The application-specific error code, expressed as a string value.
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Set an application-specific error code.
     *
     * @param string|null $code
     * @return $this
     */
    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * A short, human-readable summary of the problem.
     *
     * The title SHOULD NOT change from occurrence to occurrence of the problem,
     * except for purposes of localization.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set a short, human-readable summary of the problem.
     *
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * A human-readable explanation specific to this occurrence of the problem.
     *
     * Like title, this field’s value can be localized.
     *
     * @return string|null
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * Set a human-readable explanation specific to this occurrence of the problem.
     *
     * @param string|null $detail
     * @return $this
     */
    public function setDetail(?string $detail): self
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * An array containing references to the source of the error.
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source ?: null;
    }

    /**
     * Set an array containing references to the source of the error.
     *
     * @param iterable|null $source
     * @return $this
     */
    public function setSource(?iterable $source): self
    {
        $this->source = collect($source)->toArray();

        return $this;
    }

    /**
     * Set a JSON pointer to the source of the error.
     *
     * A JSON Pointer [RFC6901] to the associated entity in the request document
     * [e.g. "/data" for a primary data object, or "/data/attributes/title"
     * for a specific attribute].
     *
     * @param string|null $pointer
     * @return $this
     */
    public function setSourcePointer(?string $pointer): self
    {
        if (is_null($pointer)) {
            unset($this->source[self::SOURCE_POINTER]);
        } else {
            $this->source[self::SOURCE_POINTER] = $pointer;
        }

        return $this;
    }

    /**
     * Set a string indicating which URI query parameter caused the error.
     *
     * @param string|null $parameter
     * @return $this
     */
    public function setSourceParameter(?string $parameter): self
    {
        if (is_null($parameter)) {
            unset($this->source[self::SOURCE_PARAMETER]);
        } else {
            $this->source[self::SOURCE_PARAMETER] = $parameter;
        }

        return $this;
    }

    /**
     * @return array|null
     */
    public function getLinks(): ?array
    {
        return $this->links ?: null;
    }

    /**
     * @param iterable $links
     * @return $this
     */
    public function setLinks(?iterable $links): self
    {
        $this->links = collect($links)->map(function ($link) {
            return Link::cast($link);
        })->all();

        return $this;
    }

    /**
     * Set a link.
     *
     * @param string $key
     * @param Link|string|array|null $link
     * @return Error
     */
    public function setLink(string $key, $link): self
    {
        if (is_null($link)) {
            unset($this->links[$key]);
        } else {
            $this->links[$key] = Link::cast($link);
        }

        return $this;
    }

    /**
     * Set a link that leads to further details about this particular occurrence of the problem.
     *
     * @param Link|string|array $link
     * @return $this
     */
    public function setAboutLink($link): self
    {
        return $this->setLink(self::LINKS_ABOUT, $link);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_filter([
            self::ID => $this->getId(),
            self::LINKS => collect($this->getLinks())->toArray() ?: null,
            self::STATUS => $this->getStatus(),
            self::CODE => $this->getCode(),
            self::TITLE => $this->getTitle(),
            self::DETAIL => $this->getDetail(),
            self::SOURCE => $this->getSource(),
            self::META => $this->getMeta(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            self::ID => $this->getId(),
            self::LINKS => $this->getLinks(),
            self::STATUS => $this->getStatus(),
            self::CODE => $this->getCode(),
            self::TITLE => $this->getTitle(),
            self::DETAIL => $this->getDetail(),
            self::SOURCE => $this->getSource(),
            self::META => $this->getMeta(),
        ]);
    }

}
