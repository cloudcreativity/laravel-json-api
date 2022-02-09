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

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str as IlluminateStr;
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
     * @return int
     */
    public function handle()
    {
        if (!$this->apiRepository->exists($api = $this->getApiName())) {
            $this->error("JSON API '$api' does not exist.");
            return 1;
        }

        return (parent::handle() !== false) ? 0 : 1;
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
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $resolver = $this->getApi()->getDefaultResolver();
        $method = "get{$this->type}ByResourceType";

        return $resolver->{$method}($this->getResourceName());
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
            ->replaceClassName($stub, $name)
            ->replaceResourceType($stub)
            ->replaceApplicationNamespace($stub)
            ->replaceRecord($stub)
            ->replaceModelNamespace($stub);

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
            ['api', InputArgument::OPTIONAL, "The API that the resource belongs to."],
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
    protected function getResourceName()
    {
        $name = ucwords($this->getResourceInput());

        if ($this->isByResource()) {
            return IlluminateStr::plural($name);
        }

        return $name;
    }

    /**
     * @return string
     */
    protected function getResourceInput()
    {
        return $this->argument('resource');
    }

    /**
     * Replace the value of the resource type string.
     *
     * @param mixed $stub
     * @return $this
     */
    protected function replaceResourceType(&$stub)
    {
        $resource = $this->getResourceName();
        $stub = str_replace('dummyResourceType', Str::dasherize($resource), $stub);

        return $this;
    }

    /**
     * Replace the value of the model class name.
     *
     * @param $stub
     * @return $this
     */
    protected function replaceRecord(&$stub)
    {
        $resource = $this->getResourceName();
        $stub = str_replace('DummyRecord', Str::classify(IlluminateStr::singular($resource)), $stub);

        return $this;
    }

    /**
     * Replace the value of the application namespace.
     *
     * @param $stub
     * @return $this
     */
    protected function replaceApplicationNamespace(&$stub)
    {
        $namespace = rtrim($this->laravel->getNamespace(), '\\');
        $stub = str_replace('DummyApplicationNamespace', $namespace, $stub);

        return $this;
    }

    /**
     * Replace the class name.
     *
     * @param $stub
     * @return $this
     */
    protected function replaceClassName(&$stub, $name)
    {
        $stub = $this->replaceClass($stub, $name);

        return $this;
    }

    /**
     * Replace the model namespace name.
     *
     * @param $stub
     * @return $this
     */
    private function replaceModelNamespace(&$stub) {

        $modelNamespace = $this->getApi()->getModelNamespace() ?? rtrim($this->laravel->getNamespace(), "\\");
        $stub = str_replace('DummyModelNamespace', $modelNamespace, $stub);

        return $this;
    }

    /**
     * Get the stub for specific generator type
     *
     * @param string $implementationType
     * @return string
     */
    protected function getStubFor($implementationType)
    {
        return sprintf(
            '%s/%s/%s.stub',
            $this->stubsDirectory,
            $implementationType,
            Str::dasherize($this->type)
        );
    }

    /**
     * @return bool
     */
    protected function isByResource()
    {
        return $this->getApi()->isByResource();
    }

    /**
     * Determine whether a resource is eloquent or not
     *
     * @return boolean
     */
    protected function isEloquent()
    {
        if ($this->isIndependent) {
            return false;
        }

        if ($this->option('no-eloquent')) {
            return false;
        }

        return $this->option('eloquent') ?: $this->getApi()->isEloquent();
    }

    /**
     * @return Api
     */
    protected function getApi()
    {
        return $this->apiRepository->createApi($this->getApiName());
    }

    /**
     * @return string
     */
    protected function getApiName()
    {
        return $this->argument('api') ?: LaravelJsonApi::$defaultApi;
    }
}
