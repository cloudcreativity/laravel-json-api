<?php

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Utils\Pointer as P;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class ResourceObject implements Arrayable, \IteratorAggregate
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $id;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $relationships;

    /**
     * @var array
     */
    private $meta;

    /**
     * @var array
     */
    private $links;

    /**
     * @var Collection
     */
    private $fields;

    /**
     * Create a resource object from the data member of a JSON document.
     *
     * @param array $data
     * @return ResourceObject
     */
    public static function create(array $data)
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('Expecting a resource type.');
        }

        return new self(
            $data['type'],
            isset($data['id']) ? $data['id'] : null,
            isset($data['attributes']) ? $data['attributes'] : [],
            isset($data['relationships']) ? $data['relationships'] : [],
            isset($data['meta']) ? $data['meta'] : [],
            isset($data['links']) ? $data['links'] : []
        );
    }

    /**
     * ResourceObject constructor.
     *
     * @param $type
     * @param $id
     * @param array $attributes
     * @param array $relationships
     * @param array $meta
     * @param array $links
     */
    public function __construct(
        $type,
        $id,
        array $attributes,
        array $relationships,
        array $meta = [],
        array $links = []
    ) {
        $this->type = $type;
        $this->id = $id ?: null;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
        $this->meta = $meta;
        $this->links = $links;

        $this->fields = collect($attributes)->merge($this->getRelations())->merge([
            'type' => $this->type,
            'id' => $this->id,
        ])->sortKeys();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getAttributes()
    {
        return collect($this->attributes);
    }

    /**
     * Is the field an attribute?
     *
     * @param $field
     * @return bool
     */
    public function isAttribute($field)
    {
        return array_key_exists($field, $this->attributes);
    }

    /**
     * @return Collection
     */
    public function getRelationships()
    {
        return collect($this->relationships);
    }

    /**
     * Is the field a relationship?
     *
     * @param $field
     * @return bool
     */
    public function isRelationship($field)
    {
        return array_key_exists($field, $this->relationships);
    }

    /**
     * Get the data value of all relationships.
     *
     * @return Collection
     */
    public function getRelations()
    {
        return $this->getRelationships()->filter(function (array $relation) {
            return isset($relation['data']);
        })->map(function (array $relation) {
            return $relation['data'];
        });
    }

    /**
     * @return Collection
     */
    public function getMeta()
    {
        return collect($this->meta);
    }

    /**
     * @return Collection
     */
    public function getLinks()
    {
        return collect($this->links);
    }

    /**
     * Get a field value.
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function get($field, $default = null)
    {
        return $this->fields->get($field, $default);
    }

    /**
     * Do the fields exist?
     *
     * @param string ...$fields
     * @return bool
     */
    public function has(...$fields)
    {
        return $this->fields->has($fields);
    }

    /**
     * Get all the field names.
     *
     * @return Collection
     */
    public function fields()
    {
        return $this->fields->keys();
    }

    /**
     * Convert a validation key to a JSON pointer.
     *
     * @param $key
     * @return string
     */
    public function pointer($key)
    {
        if ('type' === $key) {
            return P::dataType();
        }

        if ('id' === $key) {
            return P::dataId();
        }

        $parts = collect(explode('.', $key));
        $field = $parts->first();

        if ($this->isAttribute($field)) {
            return P::dataAttribute($parts->implode('/'));
        }

        if ($this->isRelationship($field)) {
            $name = 1 < $parts->count() ? $field . '/' . $parts->put(0, 'data')->implode('/') : $field;
            return P::dataRelationship($name);
        }

        return P::data();
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->fields->all();
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return $this->fields->getIterator();
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return collect([
            'type' => $this->type,
            'id' => $this->id,
            'attributes' => $this->attributes,
            'relationships' => $this->relationships,
            'links' => $this->links,
            'meta' => $this->meta,
        ])->filter()->all();
    }

}
