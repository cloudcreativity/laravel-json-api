<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\HasOne;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class HasOneTest extends TestCase
{

    /**
     * @return array
     */
    public function validProvider(): array
    {
        return [
            'null' => [
                'users',
                null,
            ],
            'identifier' => [
                'users',
                ['type' => 'users', 'id' => '123'],
            ],
            'polymorph null' => [
                ['users', 'people'],
                null,
            ],
            'polymorph identifier 1' => [
                ['users', 'people'],
                ['type' => 'users', 'id' => '123'],
            ],
            'polymorph identifier 2' => [
                ['users', 'people'],
                ['type' => 'people', 'id' => '456'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function invalidProvider(): array
    {
        return [
            'empty has-many' => [
                'users',
                [],
            ],
            'has-many' => [
                'users',
                [
                    ['type' => 'users', 'id' => '123'],
                ],
            ],
            'invalid type' => [
                'users',
                ['type' => 'people', 'id' => '456'],
            ],
            'invalid polymorph type' => [
                ['users', 'people'],
                ['type' => 'foobar', 'id' => '1'],
            ],
        ];
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider validProvider
     */
    public function testValid($types, $value): void
    {
        $types = (array) $types;
        $rule = new HasOne(...$types);

        $this->assertTrue($rule->passes('author', $value));
    }

    public function testValidWithAttributeName(): void
    {
        $rule = new HasOne();

        $this->assertTrue($rule->passes('author', ['type' => 'authors', 'id' => '1']));
        $this->assertFalse($rule->passes('author', ['type' => 'users', 'id' => '1']));
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testInvalid($types, $value): void
    {
        $types = (array) $types;
        $rule = new HasOne(...$types);

        $this->assertFalse($rule->passes('author', $value));
    }
}
