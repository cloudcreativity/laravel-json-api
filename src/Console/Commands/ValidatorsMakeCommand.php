<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class ValidatorsMakeCommand extends JsonApiGeneratorCommand
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
    protected $description = 'Create a new json-api resource search';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Validators';

    /**
     * Whether the resource type is non-dependant on eloquent
     *
     * @var mixed
     */
    protected $isIndependant = true;
}
