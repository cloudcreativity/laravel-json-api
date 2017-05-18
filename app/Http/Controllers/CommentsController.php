<?php

namespace App\Http\Controllers;

class CommentsController
{

    /**
     * @return string
     */
    public function index()
    {
        return 'comments:index';
    }

    /**
     * @return string
     */
    public function create()
    {
        return 'comments:create';
    }

    /**
     * @param $id
     * @return string
     */
    public function read($id)
    {
        return "comments:read:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function update($id)
    {
        return "comments:update:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function delete($id)
    {
        return "comments:delete:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelatedResource($id)
    {
        return "comments:read-related:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelationship($id)
    {
        return "comments:read-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function replaceRelationship($id)
    {
        return "comments:replace-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function addToRelationship($id)
    {
        return "comments:add-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function removeFromRelationship($id)
    {
        return "comments:remove-relationship:$id";
    }
}
