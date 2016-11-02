<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

use Illuminate\Console\Command;

class ResourceMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:json-api:resource
                                {resource : The resource to create files for}
                                {--eloquent : Use eloquent as adapter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full Json Api resource.';

    private $commands = [
        'make:json-api:hydrator',
        'make:json-api:schema',
        'make:json-api:search',
        'make:json-api:request',
        'make:json-api:validators',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

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
        $eloquentParameter = [
            '--eloquent' => $this->option('eloquent'),
        ];

        // Call independent generators
        $this->call('make:json-api:validators', $resourceParameter);
        $this->call('make:json-api:request', $resourceParameter);

        // Call configurable commands
        $this->call('make:json-api:hydrator', array_merge($resourceParameter, $eloquentParameter));
        $this->call('make:json-api:schema', array_merge($resourceParameter, $eloquentParameter));
        $this->call('make:json-api:search', array_merge($resourceParameter, $eloquentParameter));
    }
}
