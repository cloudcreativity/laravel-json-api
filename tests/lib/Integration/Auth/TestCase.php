<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;
use DummyApp\User;

class TestCase extends BaseTestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @param bool $author
     * @return $this
     */
    protected function actingAsUser($author = false)
    {
        $this->actingAs(factory(User::class)->create(compact('author')));

        return $this;
    }
}
