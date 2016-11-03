<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

use Illuminate\Console\Command;
use CloudCreativity\LaravelJsonApi\Console\Commands\RequestMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\ValidatorsMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\HydratorMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\SchemaMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\SearchMakeCommand;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ResourceMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string */
    protected $name = 'make:json-api:resource';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full Json Api resource.';

    /**
     * The available generator commands.
     *
     * @var array
     */
    private $commands = [
        'make:json-api:request' => RequestMakeCommand::class,
        'make:json-api:validators' => ValidatorsMakeCommand::class,

        'make:json-api:hydrator' => HydratorMakeCommand::class,
        'make:json-api:schema' => SchemaMakeCommand::class,
        'make:json-api:search' => SearchMakeCommand::class,
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $resourceParameter = [
            'resource' => $this->argument('resource'),
        ];
        $adapterParameters = array_merge($resourceParameter, [
            '--eloquent' => $this->option('eloquent'),
            '--no-eloquent' => $this->option('no-eloquent'),
        ]);

        $commands = collect($this->commands);

        // Filter out any commands the user asked os to.
        if($this->option('only') || $this->option('except')) {
            $type = $this->option('only') ? 'only' : 'except';

            $commands = $this->filterCommands($commands, $type);
        }

        // The search unit is only for eloquent.
        if( ! $this->isEloquent()) {
            $commands->forget('make:json-api:search');
        }

        // Run independent commands.
        $this->runCommandsWithParameters($commands->only([
            'make:json-api:request',
            'make:json-api:validators',
        ]), $resourceParameter);

        // Run adapter commands.
        $this->runCommandsWithParameters($commands->only([
            'make:json-api:hydrator',
            'make:json-api:schema',
            'make:json-api:search',
        ]), $adapterParameters);

        // Just tell the user, if no files are created
        if($commands->isEmpty()) {
            $this->info('No files created.');
        }

        // Give the user a digial high-five.
        $this->comment('All done, keep doing what you do.');
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
            ->map(function($target) use ($baseCommandName) {
                return $baseCommandName . strtolower(trim($target));
            });

        return $commands->{$type}($targetCommands->toArray());
    }

    /**
     * Runs the given commands and paases them all the given parameters.
     *
     * @param Collection $commands
     * @param array $parameters
     *
     * @return void
     */
    private function runCommandsWithParameters(Collection $commands, array $parameters)
    {
        $commands->keys()->each(function($command) use ($parameters) {
            $this->call($command, $parameters);
        });
    }

    /**
     * Determine whether the generator should use eloquent or not.
     *
     * @return boolean
     */
    private function isEloquent()
    {
        $useEloquent = config('json-api.generator.use-eloquent', true);

        if($this->option('no-eloquent')) {
            return false;
        }

        return $this->option('eloquent') ?: $useEloquent;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['resource', InputArgument::REQUIRED, 'The resource to create files for'],
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
            ['eloquent', 'e`', InputOption::VALUE_NONE, 'Use eloquent as adapter'],
            ['no-eloquent', 'ne', InputOption::VALUE_NONE, 'Use an abstract adapter'],
            ['only', 'o', InputOption::VALUE_OPTIONAL, 'Specifiy the exact resources you\'d like.'],
            ['except', 'ex', InputOption::VALUE_OPTIONAL, 'Specifiy the resources you\'d like to skip.'],
        ];
    }
}
