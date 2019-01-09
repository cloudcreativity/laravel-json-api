<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests\Concerns;

trait RelationshipRequest
{

    use ResourceRequest;

    /**
     * Get the relationship name that the request is for.
     *
     * @return string
     */
    public function getRelationshipName(): string
    {
        return $this->getRoute()->getRelationshipName();
    }

}
