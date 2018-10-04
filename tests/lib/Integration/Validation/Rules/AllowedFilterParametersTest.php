<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\AllowedFilterParameters;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;

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

    public function testMessage()
    {
        $rule = new AllowedFilterParameters();

        $this->assertSame('Filter parameters must contain only allowed ones.', $rule->message());
    }
}
