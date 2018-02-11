<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Testing\InteractsWithModels;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    use InteractsWithModels;
}
