<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

trait DashCaseRelationUrls
{

    /**
     * @param object $resource
     * @param string $name
     * @return string
     */
    protected function getRelationshipSelfUrl($resource, $name)
    {
        return sprintf(
            '%s/%s/%s',
            $this->getSelfSubUrl($resource),
            DocumentInterface::KEYWORD_RELATIONSHIPS,
            Str::dasherize($name)
        );
    }

    /**
     * @param object $resource
     * @param string $name
     *
     * @return string
     */
    protected function getRelationshipRelatedUrl($resource, $name)
    {
        return $this->getSelfSubUrl($resource) . '/' . Str::dasherize($name);
    }
}
