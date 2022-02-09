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

namespace CloudCreativity\LaravelJsonApi\Document;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ResourceObject implements Arrayable, \IteratorAggregate, \JsonSerializable, \ArrayAccess
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
    private $fieldValues;

    /**
     * @var Collection
     */
    private $fieldNames;

    /**
     * Create a resource object from the data member of a JSON document.
     *
     * @param array $data
     * @return ResourceObject
     */
    public static function create(array $data): self
    {
        if (!isset($data['type'])) {
            throw new \InvalidArgumentException('Expecting a resource type.');
        }

        return new self(
            $data['type'],
            $data['id'] ?? null,
            $data['attributes'] ?? [],
            $data['relationships'] ?? [],
            $data['meta'] ?? [],
            $data['links'] ?? []
        );
    }

    /**
     * ResourceObject constructor.
     *
     * @param string $type
     * @param string|null $id
     * @param array $attributes
     * @param array $relationships
     * @param array $meta
     * @param array $links
     */
    public function __construct(
        string $type,
        ?string $id,
        array $attributes,
        array $relationships = [],
        array $meta = [],
        array $links = []
    ) {
        if (empty($type)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $this->type = $type;
        $this->id = $id ?: null;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
        $this->meta = $meta;
        $this->links = $links;
        $this->normalize();
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->fieldNames = clone $this->fieldNames;
        $this->fieldValues = clone $this->fieldValues;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function __get($field)
    {
        return $this->offsetGet($field);
    }

    /**
     * @param $field
     * @param $value
     */
    public function __set($field, $value)
    {
        throw new \LogicException('Resource object is immutable.');
    }

    /**
     * @param $field
     * @return bool
     */
    public function __isset($field): bool
    {
        return $this->offsetExists($field);
    }

    /**
     * @param $field
     */
    public function __unset($field): void
    {
        throw new \LogicException('Resource object is immutable.');
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return $this->fieldValues->has($offset);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->fieldValues->offsetGet($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        throw new \LogicException('Resource object is immutable.');
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        throw new \LogicException('Resource object is immutable.');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return a new instance with the specified type.
     *
     * @param string $type
     * @return ResourceObject
     */
    public function withType(string $type): self
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Expecting a non-empty string.');
        }

        $copy = clone $this;
        $copy->type = $type;
        $copy->normalize();

        return $copy;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Return a new instance with the specified id.
     *
     * @param string|null $id
     * @return ResourceObject
     */
    public function withId(?string $id): self
    {
        $copy = clone $this;
        $copy->id = $id ?: null;
        $copy->normalize();

        return $copy;
    }

    /**
     * Return a new instance without an id.
     *
     * @return ResourceObject
     */
    public function withoutId(): self
    {
        return $this->withId(null);
    }

    /**
     * @return Collection
     */
    public function getAttributes(): Collection
    {
        return collect($this->attributes);
    }

    /**
     * Is the field an attribute?
     *
     * @param string $field
     * @return bool
     */
    public function isAttribute(string $field): bool
    {
        return array_key_exists($field, $this->attributes);
    }

    /**
     * Return a new instance with the provided attributes.
     *
     * @param array|Collection $attributes
     * @return ResourceObject
     */
    public function withAttributes($attributes): self
    {
        $copy = clone $this;
        $copy->attributes = collect($attributes)->all();
        $copy->normalize();

        return $copy;
    }

    /**
     * Return a new instance without attributes.
     *
     * @return ResourceObject
     */
    public function withoutAttributes(): self
    {
        return $this->withAttributes([]);
    }

    /**
     * @return Collection
     */
    public function getRelationships(): Collection
    {
        return collect($this->relationships);
    }

    /**
     * Is the field a relationship?
     *
     * @param string $field
     * @return bool
     */
    public function isRelationship(string $field): bool
    {
        return array_key_exists($field, $this->relationships);
    }

    /**
     * Return a new instance with the provided relationships.
     *
     * @param array|Collection $relationships
     * @return ResourceObject
     */
    public function withRelationships($relationships): self
    {
        $copy = clone $this;
        $copy->relationships = collect($relationships)->all();
        $copy->normalize();

        return $copy;
    }

    /**
     * Return a new instance without relationships.
     *
     * @return ResourceObject
     */
    public function withoutRelationships(): self
    {
        return $this->withRelationships([]);
    }

    /**
     * Get the data value of all relationships.
     *
     * @return Collection
     */
    public function getRelations(): Collection
    {
        return $this->getRelationships()->filter(function (array $relation) {
            return array_key_exists('data', $relation);
        })->map(function (array $relation) {
            return $relation['data'];
        });
    }

    /**
     * @return Collection
     */
    public function getMeta(): Collection
    {
        return collect($this->meta);
    }

    /**
     * Return a new instance with the provided meta.
     *
     * @param array|Collection $meta
     * @return ResourceObject
     */
    public function withMeta($meta): self
    {
        $copy = clone $this;
        $copy->meta = collect($meta)->all();

        return $copy;
    }

    /**
     * Return a new instance without meta.
     *
     * @return ResourceObject
     */
    public function withoutMeta(): self
    {
        return $this->withMeta([]);
    }

    /**
     * @return Collection
     */
    public function getLinks(): Collection
    {
        return collect($this->links);
    }

    /**
     * Return a new instance with the provided links.
     *
     * @param $links
     * @return ResourceObject
     */
    public function withLinks($links): self
    {
        $copy = clone $this;
        $copy->links = collect($links)->all();

        return $copy;
    }

    /**
     * Return a new instance without links.
     *
     * @return ResourceObject
     */
    public function withoutLinks(): self
    {
        return $this->withLinks([]);
    }

    /**
     * Get all the field names.
     *
     * @return Collection
     */
    public function fields(): Collection
    {
        return $this->fieldNames->values();
    }

    /**
     * Get a field value.
     *
     * @param string $field
     * @param mixed $default
     * @return mixed
     */
    public function get(string $field, $default = null)
    {
        return Arr::get($this->all(), $field, $default);
    }

    /**
     * Do the fields exist?
     *
     * @param string ...$fields
     * @return bool
     */
    public function has(string ...$fields): bool
    {
        return $this->fieldNames->has($fields);
    }

    /**
     * Return a new instance with the supplied attribute/relationship fields removed.
     *
     * @param string ...$fields
     * @return ResourceObject
     */
    public function forget(string ...$fields): self
    {
        $copy = clone $this;
        $copy->attributes = $this->getAttributes()->forget($fields)->all();
        $copy->relationships = $this->getRelationships()->forget($fields)->all();
        $copy->normalize();

        return $copy;
    }

    /**
     * Return a new instance that only has the specified attribute/relationship fields.
     *
     * @param string ...$fields
     * @return ResourceObject
     */
    public function only(string ...$fields): self
    {
        $forget = $this->fields()->reject(function ($value) use ($fields) {
            return in_array($value, $fields, true);
        });

        return $this->forget(...$forget);
    }

    /**
     * Return a new instance with a new attribute/relationship field value.
     *
     * The field must exist, otherwise it cannot be determined whether to replace
     * either an attribute or a relationship.
     *
     * If the field is a relationship, the `data` member of that relationship will
     * be replaced.
     *
     * @param string $field
     * @param $value
     * @return ResourceObject
     * @throws \OutOfBoundsException if the field does not exist.
     */
    public function replace(string $field, $value): self
    {
        if ('type' === $field) {
            return $this->putIdentifier($value, $this->id);
        }

        if ('id' === $field) {
            return $this->putIdentifier($this->type, $value);
        }

        if ($this->isAttribute($field)) {
            return $this->putAttr($field, $value);
        }

        if ($this->isRelationship($field)) {
            return $this->putRelation($field, $value);
        }

        throw new \OutOfBoundsException("Field {$field} is not an attribute or relationship.");
    }

    /**
     * Set a field.
     *
     * Sets the provided value as a relation if it is already defined as a relation.
     * Otherwise, sets it as an attribute.
     *
     * @param string $field
     * @param mixed|null $value
     * @return ResourceObject
     */
    public function put(string $field, $value): self
    {
        if ($this->isRelationship($field)) {
            return $this->putRelation($field, $value);
        }

        return $this->putAttr($field, $value);
    }

    /**
     * Set an attribute.
     *
     * @param string $field
     * @param mixed|null $value
     * @return ResourceObject
     */
    public function putAttr(string $field, $value): self
    {
        $copy = clone $this;
        $copy->attributes[$field] = $value;
        $copy->normalize();

        return $copy;
    }

    /**
     * Set a relation.
     *
     * @param string $field
     * @param array|null $value
     * @return ResourceObject
     */
    public function putRelation(string $field, ?array $value): self
    {
        $copy = clone $this;
        $copy->relationships[$field] = $copy->relationships[$field] ?? [];
        $copy->relationships[$field]['data'] = $value;
        $copy->normalize();

        return $copy;
    }

    /**
     * Convert a validation key to a JSON pointer.
     *
     * @param string $key
     * @param string $prefix
     * @return string
     */
    public function pointer(string $key, string $prefix = ''): string
    {
        $prefix = rtrim($prefix, '/');

        if ('type' === $key) {
            return $prefix . '/type';
        }

        if ('id' === $key) {
            return $prefix . '/id';
        }

        $parts = collect(explode('.', $key));
        $field = $parts->first();

        if ($this->isAttribute($field)) {
            return $prefix . '/attributes/' . $parts->implode('/');
        }

        if ($this->isRelationship($field)) {
            $name = 1 < $parts->count() ? $field . '/' . $parts->put(0, 'data')->implode('/') : $field;
            return $prefix . "/relationships/{$name}";
        }

        return $prefix ? $prefix : '/';
    }

    /**
     * Convert a validation key to a JSON pointer for a relationship object within the resource.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function pointerForRelationship(string $key, string $default = '/'): string
    {
        $field = collect(explode('.', $key))->first();

        if (!$this->isRelationship($field)) {
            throw new \InvalidArgumentException("Field {$field} is not a relationship.");
        }

        $pointer = $this->pointer($key);

        return Str::after($pointer, "relationships/{$field}") ?: $default;
    }

    /**
     * Get the values of all fields.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->fieldValues->all();
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->fieldValues->getIterator();
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

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return Collection
     */
    private function fieldValues(): Collection
    {
        return collect($this->attributes)->merge($this->getRelations())->merge([
            'type' => $this->type,
            'id' => $this->id,
        ])->sortKeys();
    }

    /**
     * @return Collection
     */
    private function fieldNames(): Collection
    {
        $fields = collect(['type', 'id'])
            ->merge(collect($this->attributes)->keys())
            ->merge(collect($this->relationships)->keys())
            ->sort()
            ->values();

        return $fields->combine($fields);
    }

    /**
     * @return void
     */
    private function normalize(): void
    {
        $this->fieldValues = $this->fieldValues();
        $this->fieldNames = $this->fieldNames();
    }

    /**
     * @param string $type
     * @param string|null $id
     * @return ResourceObject
     */
    private function putIdentifier(string $type, ?string $id): self
    {
        $copy = clone $this;
        $copy->type = $type;
        $copy->id = $id;
        $copy->normalize();

        return $copy;
    }

}
