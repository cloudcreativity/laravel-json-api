<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;

class ServicesTest extends TestCase
{

    /**
     * @see Issue 88
     */
    public function testDocumentWithoutInboundRequest()
    {
        $this->expectException(RuntimeException::class);
        app(DocumentInterface::class);
    }

    public function testResourceObjectWithoutInboundRequest()
    {
        $this->expectException(RuntimeException::class);
        app(ResourceObjectInterface::class);
    }

    public function testRelationshipObjectWithoutInboundRequest()
    {
        $this->expectException(RuntimeException::class);
        app(RelationshipInterface::class);
    }
}
