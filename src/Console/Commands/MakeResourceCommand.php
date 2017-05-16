<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ResourceMakeCommand
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MakeResourceCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "make:json-api:resource
        {resource : The resource to create files for.}
        {api=default : The API that the resource belongs to.}
        {--e|eloquent : Use Eloquent classes.}
        {--N|no-eloquent : Do not use Eloquent classes.}
        {--o|only= : Specify the classes to generate.}
        {--x|except= : Skip the specified classes.}
    ";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full JSON API resource.';

    /**
     * The available generator commands.
     *
     * @var array
     */
    private $commands = [
        'make:json-api:adapter' => MakeAdapterCommand::class,
        'make:json-api:hydrator' => MakeHydratorCommand::class,
        'make:json-api:schema' => MakeSchemaCommand::class,
        'make:json-api:validators' => MakeValidatorsCommand::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $resourceParameter = [
            'resource' => $this->argument('resource'),
            'api' => $this->argument('api'),
        ];

        $eloquentParameters = array_merge($resourceParameter, [
            '--eloquent' => $this->option('eloquent'),
            '--no-eloquent' => $this->option('no-eloquent'),
        ]);

        $commands = collect($this->commands);

        // Just tell the user, if no files are created
        if ($commands->isEmpty()) {
            $this->info('No files created.');
            return 0;
        }

        // Filter out any commands the user asked os to.
        if ($this->option('only') || $this->option('except')) {
            $type = $this->option('only') ? 'only' : 'except';

            $commands = $this->filterCommands($commands, $type);
        }

        // Run commands that cannot accept Eloquent parameters.
        $this->runCommandsWithParameters($commands->only([
            'make:json-api:validators',
        ]), $resourceParameter);

        // Run commands that can accept Eloquent parameters.
        $this->runCommandsWithParameters($commands->only([
            'make:json-api:adapter',
            'make:json-api:hydrator',
            'make:json-api:schema',
        ]), $eloquentParameters);

        // Give the user a digial high-five.
        $this->comment('All done, keep doing what you do.');
        return 0;
    }

    /**
     * Filters out commands using either 'except' or 'only' filter.
     *
     * @param Collection $commands
     * @param string $type
     *
     * @return Collection
     */
    private function filterCommands(Collection $commands, $type)
    {
        $baseCommandName = 'make:json-api:';
        $filterValues = explode(',', $this->option($type));

        $targetCommands = collect($filterValues)
            ->map(function ($target) use ($baseCommandName) {
                return $baseCommandName . strtolower(trim($target));
            });

        return $commands->{$type}($targetCommands->toArray());
    }

    /**
     * Runs the given commands and passes them all the given parameters.
     *
     * @param Collection $commands
     * @param array $parameters
     *
     * @return void
     */
    private function runCommandsWithParameters(Collection $commands, array $parameters)
    {
        $commands->keys()->each(function ($command) use ($parameters) {
            $this->call($command, $parameters);
        });
    }

}
