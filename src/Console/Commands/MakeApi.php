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

use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Class MakeApi
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MakeApi extends Command
{

    /**
     * @var string
     */
    protected $signature = "make:json-api
        {name? : the unique API name}
    ";

    /**
     * @var string
     */
    protected $description = "Create a new JSON API configuration file";

    /**
     * @param Filesystem $files
     * @return int
     */
    public function handle(Filesystem $files)
    {
        $name = $this->argument('name') ?: LaravelJsonApi::$defaultApi;

        if (!$name) {
            $this->error('Invalid JSON API name.');
            return 1;
        }

        $filename = sprintf('json-api-%s.php', Str::dasherize($name));

        if ($files->exists($path = config_path($filename))) {
            $this->error("JSON API '$name' already exists.");
            return 1;
        }

        $contents = $files->get(__DIR__ . '/../../../stubs/api.php');
        $files->put($path, $contents);

        $this->info("Created config file for API '$name': $filename");

        return 0;
    }
}
