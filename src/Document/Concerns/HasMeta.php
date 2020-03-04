<?php

namespace CloudCreativity\LaravelJsonApi\Document\Concerns;

trait HasMeta
{

    /**
     * @var array|null
     */
    private $meta;

    /**
     * @return array|null
     */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /**
     * @param iterable|null $meta
     * @return $this
     */
    public function setMeta(?iterable $meta): self
    {
        $this->meta = collect($meta)->toArray() ?: null;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasMeta(): bool
    {
        return !empty($this->meta);
    }
}
