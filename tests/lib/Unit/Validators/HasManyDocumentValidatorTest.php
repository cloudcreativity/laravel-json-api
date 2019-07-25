<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory as Keys;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Class HasManyDocumentValidatorTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasManyDocumentValidatorTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceTypes = ['users', 'posts'];
    }

    public function testValid()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "users",
            "id": "99"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany(false);

        $this->assertTrue($validator->isValid($document));

        return $document;
    }


    /**
     * @param Document $document
     * @depends testValid
     */
    public function testValidWithDefaultValidator(Document $document)
    {
        $validator = $this->validatorFactory->relationshipDocument();

        $this->willExist()->assertTrue($validator->isValid($document));
    }

    public function testValidPolymorph()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "users",
            "id": "99"
        },
        {
            "type": "posts",
            "id": "123"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany(false, true, null, ['users', 'posts']);

        $this->assertTrue($validator->isValid($document));
    }

    public function testValidEmpty()
    {
        $content = '{"data": []}';
        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertTrue($validator->isValid($document));
    }

    public function testDataTypeRequired()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "id": "99"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_TYPE);
    }

    public function testDataTypeNotSupported()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "posts",
            "id": "99"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/type', Keys::RELATIONSHIP_UNSUPPORTED_TYPE);
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'users');
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'posts');
    }

    public function testDataIdRequired()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "users"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataEmptyNotAllowed()
    {
        $content = '{"data": []}';
        $document = $this->decode($content);
        $validator = $this->hasMany(false);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_EMPTY_NOT_ALLOWED);
        $this->assertDetailContains($validator->getErrors(), '/data', 'empty');
    }

    public function testDataDoesNotExist()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "users",
            "id": "99"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany(false, false);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_DOES_NOT_EXIST);
        $this->assertDetailContains($validator->getErrors(), '/data', 'exist');
    }

    public function testDataAcceptable()
    {
        $content = <<<JSON_API
{
    "data": [
        {
            "type": "users",
            "id": "99"
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany(false, true, function (ResourceIdentifierInterface $identifier) {
            $this->assertEquals("users", $identifier->getType());
            $this->assertEquals("99", $identifier->getId());
            return true;
        });

        $this->assertTrue($validator->isValid($document));

        return $document;
    }

    /**
     * @param Document $document
     * @depends testDataAcceptable
     */
    public function testDataNotAcceptable(Document $document)
    {
        $validator = $this->hasMany(false, true, function () {
            return false;
        });

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_NOT_ACCEPTABLE);
        $this->assertDetailContains($validator->getErrors(), '/data', 'acceptable');
    }

    public function testDataBelongsTo()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_HAS_MANY_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', 'has-many');
    }

    public function testDataEmptyBelongsTo()
    {
        $content = '{"data": null}';
        $document = $this->decode($content);
        $validator = $this->hasMany();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_HAS_MANY_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', 'has-many');
    }

    /**
     * @param bool $allowEmpty
     * @param bool $exists
     * @param callable|null $acceptable
     * @param string $expectedType
     * @return DocumentValidatorInterface
     */
    private function hasMany(
        $allowEmpty = true,
        $exists = true,
        callable $acceptable = null,
        $expectedType = 'users'
    ) {
        $this->willExist($exists);
        $validator = $this->validatorFactory->hasMany($expectedType, $allowEmpty, $acceptable);

        return $this->validatorFactory->relationshipDocument($validator);
    }
}
