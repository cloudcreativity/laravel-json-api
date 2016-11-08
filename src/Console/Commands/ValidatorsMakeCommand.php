<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class ValidatorsMakeCommand extends AbstractGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:validators';

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
    protected $type = 'Validators';

    /**
     * Whether the resource type is non-dependent on eloquent
     *
     * @var boolean
     */
    protected $isIndependent = true;
}
