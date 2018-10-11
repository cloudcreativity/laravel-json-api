<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\AllowedFilterParameters;
use PHPUnit\Framework\TestCase;

class AllowedFilterParametersTest extends TestCase
{

    public function test()
    {
        $rule = new AllowedFilterParameters(['foo', 'bar']);

        $this->assertTrue($rule->passes('filter', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['foo' => 'foobar', 'baz' => 'bazbat']));
    }

    public function testWithMethods()
    {
        $rule = (new AllowedFilterParameters())
            ->allow('foo', 'bar', 'id')
            ->forget('id');

        $this->assertTrue($rule->passes('filter', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['foo' => 'foobar', 'baz' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['id' => '1']));
    }

    public function testAny()
    {
        $rule = new AllowedFilterParameters(null);

        $this->assertTrue($rule->passes('filter', [
            'foo' => 'bar',
        ]));
    }

}
