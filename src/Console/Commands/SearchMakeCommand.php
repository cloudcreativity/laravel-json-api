<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class SearchMakeCommand extends AbstractGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new JSON API resource search';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Search';
}
