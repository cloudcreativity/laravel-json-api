<?php
/*
 * Copyright 2022 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Console\Commands;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class MakeContentNegotiator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MakeContentNegotiator extends AbstractGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:json-api:content-negotiator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new JSON API content negotiator';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ContentNegotiator';

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

        $class = $this
            ->getApi()
            ->getDefaultResolver()
            ->getContentNegotiatorByName($name);

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
            throw new RuntimeException('Not generating a resource content negotiator.');
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
            ['name', InputArgument::REQUIRED, "The content negotiator name or resource type."],
            ['api', InputArgument::OPTIONAL, "The API that the content negotiator belongs to."],
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
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate a resource-specific content negotiator.'],
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
