<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory as Keys;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use stdClass;

/**
 * Class ResourceDocumentValidatorTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceDocumentValidatorTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resourceTypes = ['people', 'tags', 'users'];
    }

    /**
     * Test a valid create resource document.
     */
    public function testCreate()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My First Blog",
            "content": "This is my first blog post..."
        },
        "relationships": {
            "author": {
                "data": {
                    "type": "people",
                    "id": "99"
                }
            },
            "tags": {
                "data": [
                    {
                        "type": "tags",
                        "id": "1"
                    },
                    {
                        "type": "tags",
                        "id": "2"
                    }
                ]
            }
        }
    }
}
JSON_API;

        $relationships = $this
            ->relationships()
            ->hasOne('author', 'people', true)
            ->hasMany('tags', null, false);

        $document = $this->decode($content);
        $validator = $this->validator('posts', null, null, $relationships);

        $this->assertTrue($validator->isValid($document));

        return $document;
    }

    /**
     * @param Document $document
     * @depends testCreate
     */
    public function testValidWithDefaultValidator(Document $document)
    {
        $validator = $this->validatorFactory->resourceDocument();

        $this->willExist()->assertTrue($validator->isValid($document));
    }

    /**
     * Test a valid create resource document with a client generated id.
     */
    public function testCreateWithClientGeneratedId()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "ec79df88-a11a-4853-a3c9-2da5057b0f85",
        "attributes": {
            "title": "My First Blog",
            "content": "This is my first blog post..."
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts');

        $this->assertTrue($validator->isValid($document));
    }

    /**
     * Test a valid resource update document.
     */
    public function testUpdate()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Blog",
            "content": "This is my first blog post..."
        },
        "relationships": {
            "author": {
                "data": {
                    "type": "people",
                    "id": "99"
                }
            },
            "tags": {
                "data": [
                    {
                        "type": "tags",
                        "id": "1"
                    },
                    {
                        "type": "tags",
                        "id": "2"
                    }
                ]
            }
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->willExist()->assertTrue($validator->isValid($document));
    }

    public function testDataRequired()
    {
        $content = '{}';
        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataNotObject()
    {
        $content = <<<JSON_API
{
    "data": "foo"
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_OBJECT_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataTypeRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "attributes": {
            "title": "My First Blog"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

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
        "attributes": {
            "title": "My First Blog"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/type', Keys::MEMBER_STRING_EXPECTED);
    }

    public function testDataTypeEmpty()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "",
        "attributes": {
            "title": "My First Blog"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/type', Keys::MEMBER_EMPTY_NOT_ALLOWED);
    }

    public function testDataTypeNotSupported()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "people",
        "attributes": {
            "name": "John Doe"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt(
            $validator->getErrors(),
            '/data/type',
            Keys::RESOURCE_UNSUPPORTED_TYPE,
            Keys::STATUS_UNSUPPORTED_TYPE
        );
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'people');
        $this->assertDetailContains($validator->getErrors(), '/data/type', 'posts');
    }

    public function testDataTypeAcceptAny()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "people",
        "attributes": {
            "name": "John Doe"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator(null);

        $this->assertTrue($validator->isValid($document));
    }

    public function testDataIdRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($validator->getErrors(), '/data', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdNull()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": null,
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/id', Keys::MEMBER_STRING_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data/id', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdInteger()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": 1,
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/id', Keys::MEMBER_STRING_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data/id', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdEmpty()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "",
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/id', Keys::MEMBER_EMPTY_NOT_ALLOWED);
        $this->assertDetailContains($validator->getErrors(), '/data/id', DocumentInterface::KEYWORD_ID);
    }

    public function testDataIdNotSupported()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "2",
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt(
            $validator->getErrors(),
            '/data/id',
            Keys::RESOURCE_UNSUPPORTED_ID,
            Keys::STATUS_UNSUPPORTED_ID
        );
        $this->assertDetailContains($validator->getErrors(), '/data/id', '2');
        $this->assertDetailContains($validator->getErrors(), '/data/id', '1');
    }

    public function testDataAttributesNotObject()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": []
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/attributes', Keys::MEMBER_OBJECT_EXPECTED);
        $this->assertDetailContains($validator->getErrors(), '/data/attributes', DocumentInterface::KEYWORD_ATTRIBUTES);
    }

    public function testDataAttributesInvalid()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1', $this->attributes(false));

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/attributes', Keys::RESOURCE_INVALID_ATTRIBUTES);
    }

    public function testDataRelationshipsNotObject()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "My First Post"
        },
        "relationships": []
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator('posts', '1');

        $this->assertFalse($validator->isValid($document));
        $errors = $validator->getErrors();
        $this->assertErrorAt($errors, '/data/relationships', Keys::MEMBER_OBJECT_EXPECTED);
        $this->assertDetailContains($errors, '/data/relationships', DocumentInterface::KEYWORD_RELATIONSHIPS);
    }

    public function testDataRelationshipNotObject()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": "foo"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/relationships/user', Keys::MEMBER_OBJECT_EXPECTED);
    }

    public function testDataRelationshipNoData()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": {}
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $errors = $validator->getErrors();
        $this->assertErrorAt($errors, '/data/relationships/user', Keys::MEMBER_REQUIRED);
        $this->assertDetailContains($errors, '/data/relationships/user', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataRelationshipInvalidData()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": {
                "data": false
            }
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->assertFalse($validator->isValid($document));
        $errors = $validator->getErrors();
        $this->assertErrorAt($errors, '/data/relationships/user', Keys::MEMBER_RELATIONSHIP_EXPECTED);
        $this->assertDetailContains($errors, '/data/relationships/user', DocumentInterface::KEYWORD_DATA);
    }

    public function testDataNonExistingRelationship()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": {
                "data": {
                    "type": "users",
                    "id": "1"
                }
            }
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $validator = $this->validator();

        $this->willNotExist();
        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/relationships/user', Keys::RELATIONSHIP_DOES_NOT_EXIST);
    }

    public function testDataRelationshipsHasOneRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "tags": {
                "data": []
            }
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $relationships = $this->relationships()->hasOne('user', 'users', true);
        $validator = $this->validator('posts', null, null, $relationships);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/relationships', Keys::MEMBER_REQUIRED);
    }

    public function testDataRelationshipsHasManyRequired()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": {
                "data": {
                    "type": "users",
                    "id": "1"
                }
            }
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $relationships = $this->relationships()->hasMany('tags', 'tags', true);
        $validator = $this->validator('posts', null, null, $relationships);

        $this->assertFalse($validator->isValid($document));
        $this->assertErrorAt($validator->getErrors(), '/data/relationships', Keys::MEMBER_REQUIRED);
    }

    public function testContextValid()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "123",
        "attributes": {
            "title": "My first post"
        },
        "relationships": {
            "user": {
                "data": {
                    "type": "users",
                    "id": "1"
                }
            }
        }
    }
}
JSON_API;

        $called = false;
        $record = new stdClass();

        $context = function (ResourceObjectInterface $resource, $obj) use (&$called, $record) {
            $this->assertSame($record, $obj);
            $this->assertEquals('posts', $resource->getType());
            $this->assertEquals('123', $resource->getId());
            $this->assertEquals('My first post', $resource
                ->getAttributes()
                ->get('title')
            );
            $this->assertEquals('users', $resource
                ->getRelationships()
                ->getRelationship('user')
                ->getData()
                ->getType()
            );
            $called = true;
            return true;
        };

        $document = $this->decode($content);
        $validator = $this->validator('posts', '123', null, null, $context);

        $this->willExist()->assertTrue($validator->isValid($document, $record));

        if (!$called) {
            $this->fail('Context validator was not called.');
        }
    }

    public function testContextInvalid()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "attributes": {
            "title": "My first post"
        }
    }
}
JSON_API;

        $expected = Error::create([
            Error::TITLE => 'Context',
            Error::DETAIL => 'Context is invalid',
        ]);
        $expected->setSourcePointer('/data/foo');

        $context = function ($resource, $record, TestContextValidator $validator) use ($expected) {
            $validator->getErrors()->add($expected);
            return false;
        };

        $document = $this->decode($content);
        $validator = $this->validator('posts', null, null, null, $context);

        $this->assertFalse($validator->isValid($document));
        $this->assertEquals($expected, $this->findErrorAt($validator->getErrors(), '/data/foo'));
    }

    /**
     * The context validator should not be called if any other part of the
     * resource is invalid.
     */
    public function testContextNotCalledIfInvalid()
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
        $validator = $this->validator('posts', '123', null, null, function () {
            $this->fail('Context validator should not be called.');
        });

        $this->assertFalse($validator->isValid($document));
    }

    /**
     * @param $resourceType
     * @param $id
     * @param AttributesValidatorInterface|null $attributes
     * @param RelationshipsValidatorInterface|null $relationships
     * @param callable|null $context
     * @return DocumentValidatorInterface
     */
    private function validator(
        $resourceType = 'posts',
        $id = null,
        AttributesValidatorInterface $attributes = null,
        RelationshipsValidatorInterface $relationships = null,
        callable $context = null
    ) {
        $context = $context ? new TestContextValidator($context) : null;

        $resource = $this->validatorFactory->resource($resourceType, $id, $attributes, $relationships, $context);
        $validator = $this->validatorFactory->resourceDocument($resource);

        return $validator;
    }

    /**
     * @param $valid
     * @return AttributesValidatorInterface
     */
    private function attributes($valid)
    {
        $mock = $this->getMockBuilder(AttributesValidatorInterface::class)->getMock();
        $mock->method('isValid')->willReturn($valid);
        $mock->method('getErrors')->willReturn(new ErrorCollection());

        /** @var AttributesValidatorInterface $mock */
        return $mock;
    }

    /**
     * @param bool $exists
     * @return RelationshipsValidatorInterface
     */
    private function relationships($exists = true)
    {
        $this->willExist($exists);
        return $this->validatorFactory->relationships();
    }
}
