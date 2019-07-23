<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\LaravelJsonApi\Validation\Rule;

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
        $rule = Rule::hasOne(...$types);

        $this->assertTrue($rule->passes('author', $value));
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testInvalid($types, $value): void
    {
        $types = (array) $types;
        $rule = Rule::hasOne(...$types);

        $this->assertFalse($rule->passes('author', $value));
    }

    public function testAllowEmpty(): void
    {
        $this->assertFalse(
            Rule::hasOne('users')->required()->passes('author', null),
            'required'
        );

        $this->assertTrue(
            Rule::hasOne('users')->allowEmpty(true)->passes('author', null),
            'allows empty'
        );

        $this->assertFalse(
            Rule::hasOne('users')->allowEmpty(false)->passes('author', null),
            'does not allow empty'
        );

        $this->assertFalse(
            Rule::hasOne('users')->allowEmpty(true)->passes('author', []),
            'rejects empty has-many when empty allowed'
        );
    }
}
