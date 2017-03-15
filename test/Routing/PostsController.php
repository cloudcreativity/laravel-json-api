<?php

namespace CloudCreativity\LaravelJsonApi\Routing;

class PostsController
{

    public function index()
    {
        return 'index';
    }

    public function create()
    {
        return 'create';
    }

    public function read($id)
    {
        return "read:$id";
    }

    public function update($id)
    {
        return "update:$id";
    }

    public function delete($id)
    {
        return "delete:$id";
    }

    public function readRelatedResource($id, $relationshipName)
    {
        return "read-related:$id:$relationshipName";
    }

    public function readRelationship($id, $relationshipName)
    {
        return "read-relationship:$id:$relationshipName";
    }

    public function replaceRelationship($id, $relationshipName)
    {
        return "replace-relationship:$id:$relationshipName";
    }

    public function addToRelationship($id, $relationshipName)
    {
        return "add-relationship:$id:$relationshipName";
    }

    public function removeFromRelationship($id, $relationshipName)
    {
        return "remove-relationship:$id:$relationshipName";
    }
}
