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

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

use CloudCreativity\JsonApi\Utils\Str;
use CloudCreativity\LaravelJsonApi\Api\Definition;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Utils\Fqn;
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
     * The location of all generator stubs
     *
     * @var string
     */
    private $stubsDirectory;

    /**
     * @var Repository
     */
    private $apiRepository;

    /**
     * AbstractGeneratorCommand constructor.
     *
     * @param Filesystem $files
     * @param Repository $apiRepository
     */
    public function __construct(Filesystem $files, Repository $apiRepository)
    {
        parent::__construct($files);
        $this->apiRepository = $apiRepository;
        $this->stubsDirectory = __DIR__ . '/../../../stubs';
    }

    /**
     * @return bool|null
     */
    public function fire()
    {
        if (!$this->apiRepository->exists($api = $this->argument('api'))) {
            $this->error("JSON API '$api' does not exist.");
            return 1;
        }

        return (parent::fire() !== false) ? 0 : 1;
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        if (!$this->isByResource()) {
            return $this->getResourceName();
        }

        return $this->type;
    }

    /**
     * Laravel 5.3 name parsing.
     *
     * @param $name
     * @return string
     * @todo remove when dropping support for Laravel 5.3
     */
    protected function parseName($name)
    {
        return $this->qualifyClass($name);
    }

    /**
     * Laravel 5.4 name parsing.
     *
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        return call_user_func(
            Fqn::class . '::' . $this->type,
            $this->getResourceName(),
            $this->getRootNamespace(),
            $this->isByResource()
        );
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $name)
            ->replaceResourceType($stub, $this->getResourceName())
            ->replaceApplicationNamespace($stub)
            ->replaceModel($stub, $this->getResourceName());

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['resource', InputArgument::REQUIRED, "The resource for which a {$this->type} class will be generated."],
            ['api', InputArgument::OPTIONAL, "The API that the resource belongs to.", 'default'],
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
            ['eloquent', 'e', InputOption::VALUE_NONE, 'Use Eloquent classes.'],
            ['no-eloquent', 'N', InputOption::VALUE_NONE, 'Do not use Eloquent classes.'],
        ];
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
     * Get the resource name
     *
     * @return string
     */
    private function getResourceName()
    {
        $name = ucwords($this->argument('resource'));

        if ($this->isByResource()) {
            return str_plural($name);
        }

        return $name;
    }

    /**
     * Replace the value of the resource type string.
     *
     * @param mixed $stub
     * @param mixed $resource
     * @return $this
     */
    private function replaceResourceType(&$stub, $resource)
    {
        $stub = str_replace('dummyResourceType', Str::dasherize($resource), $stub);

        return $this;
    }

    /**
     * Replace the value of the model class name.
     *
     * @param $stub
     * @param $resource
     * @return $this
     */
    private function replaceModel(&$stub, $resource)
    {
        $stub = str_replace('DummyModel', Str::classify(str_singular($resource)), $stub);

        return $this;
    }

    /**
     * Replace the value of the application namespace.
     *
     * @param $stub
     * @return $this
     */
    private function replaceApplicationNamespace(&$stub)
    {
        $namespace = rtrim($this->laravel->getNamespace(), '\\');
        $stub = str_replace('DummyApplicationNamespace', $namespace, $stub);

        return $this;
    }

    /**
     * Get the stub for specific generator type
     *
     * @param string $implementationType
     * @return string
     */
    private function getStubFor($implementationType)
    {
        return sprintf('%s/%s/%s.stub', $this->stubsDirectory, $implementationType, lcfirst($this->type));
    }

    /**
     * @return bool
     */
    private function isByResource()
    {
        return $this->getApiDefinition()->isByResource();
    }

    /**
     * @return string
     */
    private function getRootNamespace()
    {
        return $this->getApiDefinition()->getRootNamespace();
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

        return $this->option('eloquent') ?: $this->getApiDefinition()->isEloquent();
    }

    /**
     * @return Definition
     */
    private function getApiDefinition()
    {
        return $this->apiRepository->retrieveDefinition($this->argument('api'));
    }
}
