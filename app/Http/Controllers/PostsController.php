<?php

namespace App\Http\Controllers;

class PostsController
{

    /**
     * @return string
     */
    public function index()
    {
        return 'posts:index';
    }

    /**
     * @return string
     */
    public function create()
    {
        return 'posts:create';
    }

    /**
     * @param $id
     * @return string
     */
    public function read($id)
    {
        return "posts:read:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function update($id)
    {
        return "posts:update:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function delete($id)
    {
        return "posts:delete:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelatedResource($id)
    {
        return "posts:read-related:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function readRelationship($id)
    {
        return "posts:read-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function replaceRelationship($id)
    {
        return "posts:replace-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function addToRelationship($id)
    {
        return "posts:add-relationship:$id";
    }

    /**
     * @param $id
     * @return string
     */
    public function removeFromRelationship($id)
    {
        return "posts:remove-relationship:$id";
    }
}
