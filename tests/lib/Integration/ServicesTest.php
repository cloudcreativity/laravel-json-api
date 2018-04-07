<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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
