<?php

/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

/**
 * Class MakeValidators
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MakeValidators extends AbstractGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:validators';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new JSON API resource validator provider';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Validators';

    /**
     * Whether the resource type is non-dependent on eloquent
     *
     * @var boolean
     */
    protected $isIndependent = true;
}
