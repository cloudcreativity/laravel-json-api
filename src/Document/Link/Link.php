<?php

namespace CloudCreativity\LaravelJsonApi\Document\Link;

use CloudCreativity\LaravelJsonApi\Document\Concerns\HasMeta;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;
use UnexpectedValueException;
use function array_filter;
use function is_array;
use function is_string;

class Link implements Arrayable, \JsonSerializable
{

    private const HREF = 'href';
    private const META = 'meta';

    use HasMeta;

    /**
     * @var string
     */
    private $href;

    /**
     * Cast a value to a link.
     *
     * @param Link|string|array $value
     * @return Link
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_array($value)) {
            return self::fromArray($value);
        }

        if (is_string($value)) {
            return new self($value);
        }

        throw new UnexpectedValueException('Expecting a link, string or array.');
    }

    /**
     * Create a link from an array.
     *
     * @param array $input
     * @return Link
     */
    public static function fromArray(array $input): self
    {
        return new self(
            $input[self::HREF] ?? '',
            $input[self::META] ?? null
        );
    }

    /**
     * Link constructor.
     *
     * @param string $href
     * @param array|null $meta
     */
    public function __construct(string $href, array $meta = null)
    {
        $this->setHref($href);
        $this->setMeta($meta);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getHref();
    }

    /**
     * @return string
     */
    public function getHref(): string
    {
        return $this->href;
    }

    /**
     * @param string $href
     * @return $this
     */
    public function setHref(string $href): self
    {
        if (empty($href)) {
            throw new InvalidArgumentException('Expecting a non-empty string href.');
        }

        $this->href = $href;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_filter([
            self::HREF => $this->getHref(),
            self::META => $this->getMeta(),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        if ($this->hasMeta()) {
            return $this->toArray();
        }

        return $this->getHref();
    }

}
