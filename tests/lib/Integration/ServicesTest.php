<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;

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
