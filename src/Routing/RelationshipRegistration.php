<?php

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Support\Arrayable;

class RelationshipRegistration implements Arrayable
{

    /**
     * @var array
     */
    private $options;

    /**
     * RelationshipRegistration constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param string $resourceType
     * @return $this
     */
    public function inverse(string $resourceType): self
    {
        $this->options['inverse'] = $resourceType;

        return $this;
    }

    /**
     * @param string ...$only
     * @return $this
     */
    public function only(string ...$only): self
    {
        $this->options['only'] = $only;

        return $this;
    }

    /**
     * @param string ...$except
     * @return $this
     */
    public function except(string ...$except): self
    {
        $this->options['except'] = $except;

        return $this;
    }

    /**
     * @return RelationshipRegistration
     */
    public function readOnly(): self
    {
        return $this->only('related', 'read');
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return $this->options;
    }

}
