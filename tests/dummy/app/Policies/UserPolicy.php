<?php

namespace DummyApp\Policies;

use DummyApp\User;

class UserPolicy
{

    /**
     * @param User $user
     * @return bool
     */
    public function author(User $user)
    {
        return $user->author;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function admin(User $user)
    {
        return $user->admin;
    }
}
