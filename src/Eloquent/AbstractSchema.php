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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Support\Facades\Route;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Schema\SchemaProvider;

/**
 * Class AbstractSchema
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractSchema extends SchemaProvider
{

	public function __construct(SchemaFactoryInterface $factory)
	{
		if ($this->selfSubUrl === null) {
			$this->selfSubUrl = '/' . $this->getUri();
		}
		parent::__construct($factory);
	}

	public function getUri()
	{
		$route = Route::getCurrentRoute();
		if ($route === null) {
			return $this->getResourceType();
		}
		return $route->parameter(ResourceRegistrar::PARAM_RESOURCE_URI,$this->getResourceType());
	}

}