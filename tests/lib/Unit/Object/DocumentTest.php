<?php

namespace CloudCreativity\Utils\Object;

use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Object\ResourceObject;
use CloudCreativity\JsonApi\Object\ResourceObjectCollection;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Document\Link;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

class DocumentTest extends TestCase
{

    public function testIncluded()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "Hello World"
        },
        "relationships": {
            "author": {
                "data": {
                    "type": "users",
                    "id": "2"
                }
            }
        }
    },
    "included": [
        {
            "type": "users",
            "id": "2",
            "attributes": {
                "name": "John Doe"
            }
        }
    ]
}
JSON_API;

        $document = $this->decode($content);

        $expected = new ResourceObject((object) [
            'type' => 'users',
            'id' => '2',
            'attributes' => (object) ['name' => 'John Doe'],
        ]);

        $this->assertEquals([$expected], $document->getIncluded()->getAll());
    }

    public function testIncludedEmpty()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "Hello World"
        },
        "relationships": {
            "author": {
                "data": {
                    "type": "users",
                    "id": "2"
                }
            }
        }
    },
    "included": []
}
JSON_API;

        $document = $this->decode($content);
        $this->assertEquals(new ResourceObjectCollection(), $document->getIncluded());
    }

    public function testIncludedNotPresent()
    {
        $content = <<<JSON_API
{
    "data": {
        "type": "posts",
        "id": "1",
        "attributes": {
            "title": "Hello World"
        }
    }
}
JSON_API;

        $document = $this->decode($content);
        $this->assertNull($document->getIncluded());
    }

    public function testErrors()
    {
        $expected = new Error(
            "536d04b6-3a76-43ed-8c2f-9e60e6e68aa1",
            ['about' => new Link("http://localhost/errors/server", null, true)],
            500,
            "server",
            "Server Error",
            "An unexpected error occurred.",
            ["pointer" => "/"],
            ["foo" => "bar"]
        );

        $content = <<<JSON_API
{
    "errors": [
        {
            "id": "536d04b6-3a76-43ed-8c2f-9e60e6e68aa1",
            "links": {
                "about": "http://localhost/errors/server"
            },
            "status": 500,
            "code": "server",
            "title": "Server Error",
            "detail": "An unexpected error occurred.",
            "source": {
                "pointer": "/"
            },
            "meta": {
                "foo": "bar"
            }
        }
    ]
}
JSON_API;

        $document = $this->decode($content);
        $this->assertEquals([$expected], $document->getErrors()->getArrayCopy());
    }

    public function testErrorsEmpty()
    {
        $content = <<<JSON_API
{
    "errors": []
}
JSON_API;

        $document = $this->decode($content);
        $this->assertEquals(new ErrorCollection(), $document->getErrors());
    }

    public function testErrorsNotPresent()
    {
        $content = "{}";
        $this->assertNull($this->decode($content)->getErrors());
    }
}
