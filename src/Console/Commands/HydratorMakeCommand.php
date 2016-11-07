<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class HydratorMakeCommand extends AbstractGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:hydrator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new JSON API resource hydrator';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Hydrator';
}
