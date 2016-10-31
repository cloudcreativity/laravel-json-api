<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class SchemaMakeCommand extends JsonApiGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:schema';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new json-api resource schema';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Schema';
}
