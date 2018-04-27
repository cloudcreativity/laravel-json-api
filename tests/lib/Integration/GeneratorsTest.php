<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use Illuminate\Filesystem\Filesystem;

class GeneratorsTest extends TestCase
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var bool
     */
    private $byResource = true;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->app->setBasePath($this->path = __DIR__ . '/../../dummy');
        $this->files = new Filesystem();
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();

        $directories = [
            "{$this->path}/app/JsonApi/Companies",
            "{$this->path}/app/JsonApi/Adapters",
            "{$this->path}/app/JsonApi/Schemas",
            "{$this->path}/app/JsonApi/Validators",
        ];

        foreach ($directories as $dir) {
            if ($this->files->exists($dir)) {
                $this->files->deleteDirectory($dir);
            }
        }

        if ($this->files->exists($file = "{$this->path}/config/json-api-v1.php")) {
            $this->files->delete($file);
        }
    }

    /**
     * We can generate a new API configuration file in our application.
     */
    public function testGeneratesApi()
    {
        $result = $this->artisan('make:json-api', [
            'name' => 'v1'
        ]);

        $this->assertSame(0, $result);
        $this->assertFileEquals(
            __DIR__ . '/../../../stubs/api.php',
            "{$this->path}/config/json-api-v1.php"
        );
    }

    /**
     * If Eloquent is set as the default, running the generator without specifying
     * Eloquent will create Eloquent classes.
     */
    public function testEloquentResourceAsDefault()
    {
        $this->withEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * If Eloquent is not the default, running the generator with the Eloquent option
     * will create Eloquent classes.
     */
    public function testForceEloquentResource()
    {
        $this->withoutEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--eloquent' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * Test generating an Eloquent resource with the `by-resource` option set to `false`.
     */
    public function testEloquentResourceNotByResource()
    {
        $this->withEloquent()->notByResource();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertEloquentResource();
    }

    /**
     * If Eloquent is not the default, running the generator without specifying
     * anything will create generic classes.
     */
    public function testGenericResourceAsDefault()
    {
        $this->withoutEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * If Eloquent is the default, running the generator with the non-eloquent option
     * will create generic classes.
     */
    public function testForceGenericResource()
    {
        $this->withEloquent();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
            '--no-eloquent' => true,
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * Test generating generic resources with the `by-resource` option set to `false`.
     */
    public function testGenericResourceNotByResource()
    {
        $this->withoutEloquent()->notByResource();

        $result = $this->artisan('make:json-api:resource', [
            'resource' => 'companies',
        ]);

        $this->assertSame(0, $result);
        $this->assertGenericResource();
    }

    /**
     * @return $this
     */
    private function withEloquent()
    {
        config()->set('json-api-default.use-eloquent', true);

        return $this;
    }

    /**
     * @return $this
     */
    private function withoutEloquent()
    {
        config()->set('json-api-default.use-eloquent', false);

        return $this;
    }

    /**
     * @return $this
     */
    private function notByResource()
    {
        $this->byResource = false;
        config()->set('json-api-default.by-resource', false);

        return $this;
    }

    /**
     * @return void
     */
    private function assertEloquentResource()
    {
        $this->assertEloquentAdapter();
        $this->assertEloquentSchema();
        $this->assertValidators();
    }

    /**
     * @return void
     */
    private function assertGenericResource()
    {
        $this->assertGenericAdapter();
        $this->assertGenericSchema();
        $this->assertValidators();
    }

    /**
     * @return void
     */
    private function assertEloquentAdapter()
    {
        $content = $this->assertAdapter();

        $this->assertContains('Eloquent\AbstractAdapter', $content);
        $this->assertContains('use DummyApp\Company;', $content);
        $this->assertContains('parent::__construct(new Company(), $paging);', $content);
    }

    /**
     * @return void
     */
    private function assertGenericAdapter()
    {
        $content = $this->assertAdapter();
        $this->assertContains('Adapter\AbstractResourceAdapter', $content);
    }

    /**
     * @return string
     */
    private function assertAdapter()
    {
        $file = $this->byResource ?
            "{$this->path}/app/JsonApi/Companies/Adapter.php" :
            "{$this->path}/app/JsonApi/Adapters/Company.php";

        $this->assertFileExists($file);
        $content = $this->files->get($file);

        if ($this->byResource) {
            $this->assertContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContains('class Adapter extends', $content);
        } else {
            $this->assertContains('namespace DummyApp\JsonApi\Adapters;', $content);
            $this->assertContains('class Company extends', $content);
        }

        return $content;
    }

    /**
     * @return void
     */
    private function assertEloquentSchema()
    {
        $content = $this->assertSchema();
        $this->assertContains('Eloquent\AbstractSchema', $content);

    }

    /**
     * @return void
     */
    private function assertGenericSchema()
    {
        $content = $this->assertSchema();
        $this->assertContains('Schema\SchemaProvider', $content);
        $this->assertContains('use DummyApp\Company;', $content);
        $this->assertContains('@param Company $resource', $content);
    }

    /**
     * @return string
     */
    private function assertSchema()
    {
        $file = $this->byResource ?
            "{$this->path}/app/JsonApi/Companies/Schema.php" :
            "{$this->path}/app/JsonApi/Schemas/Company.php";

        $this->assertFileExists($file);
        $content = $this->files->get($file);
        $this->assertContains("protected \$resourceType = 'companies';", $content);

        if ($this->byResource) {
            $this->assertContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContains('class Schema extends', $content);
        } else {
            $this->assertContains('namespace DummyApp\JsonApi\Schemas;', $content);
            $this->assertContains('class Company extends', $content);
        }

        return $content;
    }

    /**
     * @return void
     */
    private function assertValidators()
    {
        $file = $this->byResource ?
            "{$this->path}/app/JsonApi/Companies/Validators.php" :
            "{$this->path}/app/JsonApi/Validators/Company.php";

        $this->assertFileExists($file);
        $content = $this->files->get($file);

        $this->assertContains("protected \$resourceType = 'companies';", $content);
        $this->assertContains('use DummyApp\Company;', $content);
        $this->assertContains('@param Company|null $record', $content);

        if ($this->byResource) {
            $this->assertContains('namespace DummyApp\JsonApi\Companies;', $content);
            $this->assertContains('class Validators extends', $content);
        } else {
            $this->assertContains('namespace DummyApp\JsonApi\Validators;', $content);
            $this->assertContains('class Company extends', $content);
        }
    }
}
