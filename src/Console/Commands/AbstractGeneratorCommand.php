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

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class AbstractGeneratorCommand
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractGeneratorCommand extends GeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type;

    /**
     * Whether the resource type is non-dependent on eloquent.
     *
     * @var boolean
     */
    protected $isIndependent = false;

    /**
     * Whether the resource should use eloquent implementations.
     *
     * @var boolean
     */
    protected $useEloquent;

    /**
     * The folder within the root namespace, where files should be generated.
     *
     * @var string
     */
    protected $subNamespace;

    /**
     * Whether generated files should be grouped by their resource files.
     *
     * @var mixed
     */
    protected $namespaceByResource;

    /**
     * The location of all generator stubs
     *
     * @var string
     */
    private $stubsDirectory;

    /**
     * Create a new config clear command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->useEloquent = config('json-api.generator.use-eloquent', true);
        $this->subNamespace = config('json-api.generator.namespace', 'JsonApi');
        $this->namespaceByResource = config('json-api.generator.by-resource', true);
        $this->stubsDirectory = __DIR__ . '/../../../stubs';
    }

    /**
     * Build the class with the given name.
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $name)
            ->replaceResourceType($stub, $this->getResourceName());

        return $stub;
    }

    /**
     * Replace the value of the resource type constant
     *
     * @param mixed $stub
     * @param mixed $resource
     * @return $this
     */
    protected function replaceResourceType(&$stub, $resource)
    {
        $stub = str_replace('dummyResourceType', snake_case($resource, '-'), $stub);

        return $this;
    }

    /**
     * Get the resource name
     *
     * @return string
     */
    protected function getResourceName()
    {
        $name = ucwords($this->argument('resource'));

        if ($this->namespaceByResource) {
            return str_plural($name);
        }

        return $name;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        if (!$this->namespaceByResource) {
            return $this->getResourceName();
        }

        return $this->type;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if ($this->isIndependent) {
            return $this->getStubFor('independent');
        }

        if ($this->isEloquent()) {
            return $this->getStubFor('eloquent');
        }

        return $this->getStubFor('abstract');
    }

    /**
     * Get the stub for specific generator type
     *
     * @param string $implementationType
     * @return string
     */
    private function getStubFor($implementationType)
    {
        return implode('', [
            $this->stubsDirectory,
            '/',
            $implementationType,
            '/',
            lcfirst($this->type),
            '.stub',
        ]);
    }

    /**
     * Determine whether a resource is eloquent or not
     *
     * @return boolean
     */
    private function isEloquent()
    {
        if ($this->isIndependent) {
            return false;
        }

        if ($this->option('no-eloquent')) {
            return false;
        }

        return $this->option('eloquent') ?: $this->useEloquent;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        $namespace = [
            $rootNamespace,             // #0
            '\\',                       // #1
            $this->subNamespace,        // #2
            '\\',                       // #3
            $this->getResourceName()    // #4
        ];

        if (!$this->namespaceByResource) {
            $namespace[4] = str_plural($this->type);
        }

        return implode('', $namespace);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['resource', InputArgument::REQUIRED, "The resource for which a {$this->type} class will be generated"],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        if ($this->isIndependent) {
            return [];
        }

        return [
            ['eloquent', 'e', InputOption::VALUE_NONE, 'Use eloquent as adapter'],
            ['no-eloquent', 'ne', InputOption::VALUE_NONE, 'Use an abstract adapter'],
        ];
    }
}
