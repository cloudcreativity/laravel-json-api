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
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AcceptRelatedResourceInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection;
use CloudCreativity\LaravelJsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Validators\AcceptImmutableRelationship;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory as Keys;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Class HasOneDocumentValidatorTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasOneDocumentValidatorTest extends TestCase
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
    "data": {
        "type": "users",
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

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
    "data": {
        "type": "posts",
        "id": "123"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne(true, true, null, ['users', 'posts']);

        $this->assertTrue($validator->isValid($document));
    }

    public function testValidEmpty()
    {
        $content = '{"data": null}';
        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertTrue($validator->isValid($document));
    }

    public function testDataRequired()
    {
        $content = '{}';
        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataNotRelationship()
    {
        $content = '{"data": "foo"}';
        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_RELATIONSHIP_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataTypeRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_TYPE);
    }

    public function testDataTypeNull()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": null,
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_STRING_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_TYPE);
    }

    public function testDataTypeEmptyString()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "",
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_EMPTY_NOT_ALLOWED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_TYPE);
    }

    public function testDataTypeUnknown()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "foobar",
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/type', Keys::RELATIONSHIP_UNKNOWN_TYPE);
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'not recognised');
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'foobar');
    }

    public function testDataTypeNotSupported()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "99"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/type', Keys::RELATIONSHIP_UNSUPPORTED_TYPE);
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'users');
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'posts');
    }

    public function testDataIdRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users"
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdNull()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": null
    }
}
JSON_API;

        $document=  $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_STRING_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdInteger()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": 99
    }
}
JSON_API;

        $document=  $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_STRING_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdEmptyString()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": ""
    }
}
JSON_API;

        $document=  $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_EMPTY_NOT_ALLOWED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataEmptyNotAllowed()
    {
        $content = '{"data": null}';
        $document = $this->decode($content);
        $validator = $this->hasOne(false);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_EMPTY_NOT_ALLOWED);
        $this->assertDetailContains($validator->getErrors(), '/data', 'empty');
    }

    public function testDataDoesNotExist()
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
        $validator = $this->hasOne(false, false);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt(
            $validator->getErrors(),
            '/data',
            Keys::RELATIONSHIP_DOES_NOT_EXIST,
            Keys::STATUS_RELATED_RESOURCE_DOES_NOT_EXIST
        );
        $this->assertDetailContains($validator->getErrors(), '/data', 'exist');
    }

    public function testDataAcceptable()
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
        $validator = $this->hasOne(false, true, function (ResourceIdentifierInterface $identifier) {
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
        $validator = $this->hasOne(false, true, function () {
            return false;
        });

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_NOT_ACCEPTABLE);
        $this->assertDetailContains($validator->getErrors(), '/data', 'acceptable');
    }

    public function testDataAcceptableReturnsError()
    {
        $content = '{"data": {"type": "users", "id": "99"}}';
        $document = $this->decode($content);
        $validator = $this->hasOne(false, true, function () {
            $error = new Error();
            $error->setDetail('Foobar');
            return $error;
        });

        $this->assertFalse($validator->isValid($document));
        $this->assertDetailIs($validator->getErrors(), '/data', 'Foobar');
    }

    public function testDataAcceptableReturnsErrors()
    {
        $content = '{"data": {"type": "users", "id": "99"}}';
        $document = $this->decode($content);
        $validator = $this->hasOne(false, true, function () {
            $error = new Error();
            $error->setDetail('Foobar');
            return new MutableErrorCollection([$error]);
        });

        $this->assertFalse($validator->isValid($document));
        $this->assertDetailIs($validator->getErrors(), '/data', 'Foobar');
    }

    public function testValidImmutable()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": "123"
    }
}
JSON_API;

        $immutable = new AcceptImmutableRelationship('users', 123);
        $document = $this->decode($content);
        $validator = $this->hasOne(true, true, $immutable);

        $this->assertTrue($validator->isValid($document));
    }

    public function testImmutable()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "users",
        "id": "124"
    }
}
JSON_API;

        $immutable = new AcceptImmutableRelationship('users', 123);
        $document = $this->decode($content);
        $validator = $this->hasOne(true, true, $immutable);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_NOT_ACCEPTABLE);
        $this->assertDetailContains($validator->getErrors(), '/data', 'acceptable');
    }

    public function testImmutablePolymorph()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "123"
    }
}
JSON_API;

        $immutable = new AcceptImmutableRelationship('users', '123');
        $document = $this->decode($content);
        $validator = $this->hasOne(true, true, $immutable, ['posts', 'users']);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_NOT_ACCEPTABLE);
        $this->assertDetailContains($validator->getErrors(), '/data', 'acceptable');
    }

    public function testDataHasMany()
    {
        $content = '{"data": []}';
        $document = $this->decode($content);
        $validator = $this->hasOne();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::RELATIONSHIP_HAS_ONE_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', 'has-one');
    }

    /**
     * @param bool $allowEmpty
     * @param bool $exists
     * @param AcceptRelatedResourceInterface|callable|null $acceptable
     * @param string|string[] $expectedType
     * @return DocumentValidatorInterface
     */
    private function hasOne(
        $allowEmpty = true,
        $exists = true,
        $acceptable = null,
        $expectedType = 'users'
    ) {
        $this->willExist($exists);
        $validator = $this->validatorFactory->hasOne($expectedType, $allowEmpty, $acceptable);

        return $this->validatorFactory->relationshipDocument($validator);
    }
}
