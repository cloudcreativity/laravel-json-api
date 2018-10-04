<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\DisallowedParameter;
use PHPUnit\Framework\TestCase;

class DisallowedParameterTest extends TestCase
{

    public function test()
    {
        $rule = new DisallowedParameter('include');

        $this->assertFalse($rule->passes('include', 'foo,bar'));
    }
}
