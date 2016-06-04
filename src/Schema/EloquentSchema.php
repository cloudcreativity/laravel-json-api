<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Schema;

use Carbon\Carbon;
use CloudCreativity\JsonApi\Exceptions\SchemaException;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;

/**
 * Class EloquentSchema
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class EloquentSchema extends AbstractSchema
{

    /**
     * The attribute to use for the resource id.
     *
     * If null, defaults to `Model::getKeyName()`
     *
     * @var
     */
    protected $idName;

    /**
     * Should the created at attribute be included?
     *
     * If the model does not have timestamps, then this setting will be ignored.
     *
     * @var bool
     */
    protected $createdAt = true;

    /**
     * Should the updated at attribute be included?
     *
     * If the model does not have timestamps, then this setting will be ignored.
     *
     * @var bool
     */
    protected $updatedAt = true;

    /**
     * Should the deleted at attribute be included?
     *
     * If the model does not use the `SoftDeletes` trait, this will be ignored.
     *
     * @var bool
     */
    protected $deletedAt = true;

    /**
     * The date format to use.
     *
     * If null, will default to W3C. In our experience this is the best format to
     * use, but you can easily override it here.
     *
     * @var string|null
     */
    protected $dateFormat;

    /**
     * The model attribute keys to serialize.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * @param object $resource
     * @return mixed
     */
    public function getId($resource)
    {
        if (!$resource instanceof Model) {
            throw new SchemaException('Expecting an Eloquent model.');
        }

        $key = $this->idName ?: $resource->getKeyName();

        return $resource->{$key};
    }

    /**
     * @param object $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        if (!$resource instanceof Model) {
            throw new SchemaException('Expecting an Eloquent model to serialize.');
        }

        return array_merge(
            $this->getDefaultAttributes($resource),
            $this->getModelAttributes($resource)
        );
    }

    /**
     * Get attributes that are included for every model class.
     *
     * @param Model $model
     * @return array
     */
    protected function getDefaultAttributes(Model $model)
    {
        $defaults = [];

        if ($this->hasCreatedAtAttribute($model)) {
            $createdAt = $model->getCreatedAtColumn();
            $defaults[$this->keyForAttribute($createdAt)] = $this->extractAttribute($model, $createdAt);
        }

        if ($this->hasUpdatedAtAttribute($model)) {
            $updatedAt = $model->getUpdatedAtColumn();
            $defaults[$this->keyForAttribute($updatedAt)] = $this->extractAttribute($model, $updatedAt);
        }

        if ($this->hasDeletedAtAttribute($model)) {
            $deletedAt = $model->getDeletedAtColumn();
            $defaults[$this->keyForAttribute($deletedAt)] = $this->extractAttribute($model, $deletedAt);
        }

        return $defaults;
    }

    /**
     * Get attributes for the provided model.
     *
     * @param Model $model
     * @return array
     */
    protected function getModelAttributes(Model $model)
    {
        $attributes = [];

        foreach ($this->attributes as $key) {
            $attributes[$this->keyForAttribute($key)] = $this->extractAttribute($model, $key);
        }

        return $attributes;
    }

    /**
     * @param $modelKey
     * @return string
     */
    protected function keyForAttribute($modelKey)
    {
        return $modelKey;
    }

    /**
     * @param Model $model
     * @param $modelKey
     * @return string
     */
    protected function extractAttribute(Model $model, $modelKey)
    {
        $value = $model->{$modelKey};

        return $this->serializeAttribute($value, $model, $modelKey);
    }

    /**
     * @param $value
     * @param Model $model
     * @param $modelKey
     * @return string
     */
    protected function serializeAttribute($value, Model $model, $modelKey)
    {
        if ($value instanceof DateTime) {
            $value = $value->format($this->getDateFormat());
        }

        return $value;
    }

    /**
     * Serialize an Eloquent belongs-to relationship.
     *
     * This is a more efficient serialization, because if the related model is not going
     * to be included in the JSON API response, we can use the key from the relationship.
     * This helper method takes care of that logic for you.
     *
     * @param Model $model
     * @param $modelKey
     *      the key on the model for getting the related model/relationship object.
     * @param $isIncluded
     *      whether the client has asked for the relationship to be included.
     * @param string|null $inverseResourceType
     *      the inverse resource type, defaults to the pluralized version of the model key.
     * @param mixed $meta
     *      meta to include in the relationship object.
     * @param LinkInterface[]|null $additionalLinks
     *      additional links to add to the relationship.
     * @param bool $showSelf
     *      whether the relationship self link should be included.
     * @param bool $showRelated
     *      whether the relationship related link should be included.
     * @return array
     */
    protected function serializeBelongsTo(
        Model $model,
        $modelKey,
        $isIncluded,
        $inverseResourceType = null,
        $meta = null,
        array $additionalLinks = null,
        $showSelf = true,
        $showRelated = true
    ) {
        $inverseResourceType = $inverseResourceType ?: str_plural($modelKey);

        $data = $isIncluded ?
            $model->{$modelKey} :
            $this->createIdentity($inverseResourceType, $model->{$this->keyForBelongsToId($model, $modelKey)});

        return [
            self::SHOW_SELF => $showSelf,
            self::SHOW_RELATED => $showRelated,
            self::DATA => $data,
            self::META => $meta,
            self::LINKS => $additionalLinks,
        ];
    }

    /**
     * Serialize an Eloquent relationship, including the related model(s) as data.
     *
     * @param Model $model
     * @param string $modelKey
     * @param mixed $meta
     *      meta to include in the relationship object.
     * @param LinkInterface[]|null $additionalLinks
     *      additional links to add to the relationship.
     * @param bool $showSelf
     *      whether the relationship self link should be included.
     * @param bool $showRelated
     *      whether the relationship related link should be included.
     * @return array
     */
    protected function serializeRelationship(
        Model $model,
        $modelKey,
        $meta = null,
        array $additionalLinks = null,
        $showSelf = true,
        $showRelated = true
    ) {
        return [
            self::SHOW_SELF => $showSelf,
            self::SHOW_RELATED => $showRelated,
            self::DATA => function () use ($model, $modelKey) {
                return $model->{$modelKey};
            },
            self::META => $meta,
            self::LINKS => $additionalLinks,
        ];
    }

    /**
     * @param Model $model
     * @param $modelKey
     * @return string
     */
    protected function keyForBelongsToId(Model $model, $modelKey)
    {
        $relation = $model->{$modelKey}();

        if (!$relation instanceof BelongsTo) {
            throw new SchemaException(sprintf(
                'Expecting %s on %s to be a belongs-to relationship.',
                $modelKey,
                get_class($model)
            ));
        }

        return $relation->getForeignKey();
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasCreatedAtAttribute(Model $model)
    {
        return $model->timestamps && true === $this->createdAt;
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasUpdatedAtAttribute(Model $model)
    {
        return $model->timestamps && true === $this->updatedAt;
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasDeletedAtAttribute(Model $model)
    {
        return true === $this->deletedAt && method_exists($model, 'getDeletedAtColumn');
    }

    /**
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: Carbon::W3C;
    }
}
