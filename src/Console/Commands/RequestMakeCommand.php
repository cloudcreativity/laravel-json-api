<?php

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

class RequestMakeCommand extends JsonApiGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new json-api resource request';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';
}
