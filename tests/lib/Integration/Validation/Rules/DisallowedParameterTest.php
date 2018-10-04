<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\DisallowedParameter;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

class DisallowedParameterTest extends TestCase
{

    public function test()
    {
        $rule = new DisallowedParameter('include');

        $this->assertFalse($rule->passes('include', 'foo,bar'));
        $this->assertSame("Parameter include is not allowed.", $rule->message());
    }
}
