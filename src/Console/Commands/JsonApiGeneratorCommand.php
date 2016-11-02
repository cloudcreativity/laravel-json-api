<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

use Illuminate\Console\GeneratorCommand as LaravelGeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

abstract class JsonApiGeneratorCommand extends LaravelGeneratorCommand
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
     * Whether the resource type is non-dependent on eloquent
     *
     * @var boolean
     */
    protected $isIndependent = false;

    /**
     * The location of all generator stubs
     *
     * @var string
     */
    private $stubsDirectory = __DIR__.'/../../../stubs';

    /**
     * Create a new config clear command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->subNamespace = config('json-api.generator.namespace', 'JsonApi');
        $this->useEloquent = config('json-api.generator.use_eloquent', true);
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClass($name)
    {
        $namespace = $this->getNamespace($name);

        $stub = $this->files->get($this->getStub());

        $this->replaceNamespace($stub, $name)
                ->replaceResourceType($stub, $this->getPluralizedResource());

        return $stub;
    }

    /**
     * Replace the value of the resource type constant
     *
     * @param mixed $stub
     * @param mixed $resource
     */
    protected function replaceResourceType(&$stub, $resource)
    {
        $stub = str_replace(
            'dummyResourceType', camel_case($resource), $stub
        );

        return $this;
    }

    /**
     * Get the plural version of the resource name
     *
     * @return string
     */
    protected function getPluralizedResource()
    {
        return str_plural($this->getResourceName());
    }

    /**
     * Get the resource name
     *
     * @return string
     */
    protected function getResourceName()
    {
        return ucwords($this->argument('resource'));
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->type);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        if($this->isIndependent) {
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
     * @param string mentationType
     */
    private function getStubFor($implementationType)
    {
        return implode('', [
            $this->stubsDirectory,
            '/',
            $implementationType,
            '/',
            $this->type,
            '.stub'
        ]);
    }

    /**
     * Determine whether a resource is eloquent or not
     *
     * @return boolean
     */
    private function isEloquent()
    {
        if($this->isIndependent){
            return false;
        }

        return $this->option('eloquent') ?: $this->useEloquent;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return implode('', [
            $rootNamespace,
            '\\',
            $this->subNamespace,
            '\\',
            $this->getPluralizedResource()
        ]);
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
        if($this->isIndependent) {
            return [];
        }

        return [
            ['eloquent', 'e', InputOption::VALUE_OPTIONAL, 'Use eloquent as adapter.'],
        ];
    }
}
