<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeAuthorizer
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MakeAuthorizer extends AbstractGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:authorizer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new JSON API resource authorizer';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Authorizer';

    /**
     * Whether the resource type is non-dependent on eloquent
     *
     * @var boolean
     */
    protected $isIndependent = true;

    /**
     * @param string $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        if ($this->isResource()) {
            return parent::qualifyClass($name);
        }

        $class = $this->getApi()->getDefaultResolver()->getAuthorizerByName($name);

        return $class;
    }

    /**
     * @return string
     */
    protected function getNameInput()
    {
        return $this->argument('name');
    }

    /**
     * @return string
     */
    protected function getResourceInput()
    {
        if ($this->isNotResource()) {
            throw new RuntimeException('Not generating a resource authorizer.');
        }

        return $this->argument('name');
    }

    /**
     * @inheritdoc
     */
    protected function replaceResourceType(&$stub)
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function replaceRecord(&$stub)
    {
        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, "The authorizer name or resource type."],
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
        return [
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource specific authorizer.'],
        ];
    }

    /**
     * @return bool
     */
    private function isResource()
    {
        return (bool) $this->option('resource');
    }

    /**
     * @return bool
     */
    private function isNotResource()
    {
        return !$this->isResource();
    }
}
