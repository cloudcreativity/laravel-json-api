<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Authorizer;

use CloudCreativity\JsonApi\Authorizer\ReadOnlyAuthorizer;
use CloudCreativity\JsonApi\Object\Relationship;
use CloudCreativity\JsonApi\Object\ResourceObject;
use CloudCreativity\JsonApi\Repositories\ErrorRepository;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\Utils\Object\StandardObject;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class ReadOnlyAuthorizerTest
 *
 * @package CloudCreativity\JsonApi
 */
class ReadOnlyAuthorizerTest extends TestCase
{

    public function testReadOnly()
    {
        /** @var EncodingParametersInterface $parameters */
        $parameters = $this->getMockBuilder(EncodingParametersInterface::class)->getMock();
        $authorizer = new ReadOnlyAuthorizer(new ErrorRepository());
        $record = new StandardObject();

        $this->assertTrue($authorizer->canReadMany('posts', $parameters));
        $this->assertTrue($authorizer->canRead($record, $parameters));
        $this->assertTrue($authorizer->canReadRelationship('comments', $record, $parameters));

        $this->assertFalse($authorizer->canCreate('posts', new ResourceObject(), $parameters));
        $this->assertFalse($authorizer->canUpdate($record, new ResourceObject(), $parameters));
        $this->assertFalse($authorizer->canDelete($record, $parameters));
        $this->assertFalse($authorizer->canModifyRelationship('comments', $record, new Relationship(), $parameters));
    }
}
