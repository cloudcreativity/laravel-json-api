<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace DummyApp\Policies;

use DummyApp\Post;
use DummyApp\User;
use Illuminate\Support\Facades\Gate;

class PostPolicy
{

    /**
     * Determine if the user is allowed to access posts.
     *
     * @param User $user
     * @return bool
     */
    public function access(User $user)
    {
        return true;
    }

    /**
     * Determine if the given user can create posts.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return (bool) $user->author;
    }

    /**
     * Determine if the given post can be read by the user.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function read(User $user, Post $post)
    {
        if ($post->published) {
            return true;
        }

        return $this->update($user, $post);
    }

    /**
     * Determine if the given post can be updated by the user.
     *
     * @param  User  $user
     * @param  Post  $post
     * @return bool
     */
    public function update(User $user, Post $post)
    {
        return $user->is($post->author);
    }

    /**
     * Determine if the given post can be deleted by the user.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function delete(User $user, Post $post)
    {
        return $this->update($user, $post);
    }

    /**
     * Determine if the user can comment on the given post.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function comment(User $user, Post $post)
    {
        return Gate::denies('admin', $user);
    }
}
