<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as Relation;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOne extends BelongsTo
{

    /**
     * @inheritDoc
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $relation = $this->relation($record);
        $related = $this->related($relationship);
        /** @var Model|null $current */
        $current = $record->{$this->key};

        /** If the relationship is not changing, we do not need to do anything. */
        if ($current && $related && $current->is($related)) {
            return;
        }

        /** If there is a current related model, we need to clear it. */
        if ($current) {
            $current->setAttribute($relation->getForeignKeyName(), null)->save();
        }

        /** If there is a related model, save it. */
        if ($related) {
            $relation->save($related);
        }

        // no need to refresh $record as the Eloquent adapter will do it.
    }

    /**
     * @inheritDoc
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->refresh(); // in case the relationship has been cached.
    }

    /**
     * @param $record
     * @return Relation
     */
    protected function relation($record)
    {
        $relation = $record->{$this->key}();

        if (!$relation instanceof Relation) {
            throw new RuntimeException("Model relation '{$this->key}' is not an Eloquent has-one relation.");
        }

        return $relation;
    }
}