<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;

/**
 * Class ResourceRegistration
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ResourceRegistration implements Arrayable
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var array
     */
    private $options;

    /**
     * @var RelationshipsRegistration
     */
    private $relationships;

    /**
     * @var bool
     */
    private $registered;

    /**
     * ResourceRegistration constructor.
     *
     * @param Registrar $router
     * @param string $resourceType
     * @param array $options
     */
    public function __construct(Registrar $router, string $resourceType, array $options = [])
    {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->registered = false;

        $this->options = collect($options)
            ->forget(['has-one', 'has-many'])
            ->all();

        if (isset($options['controller']) && true === $options['controller']) {
            $this->controller();
        }

        $this->relationships = new RelationshipsRegistration(
            $options['has-one'] ?? null,
            $options['has-many'] ?? null
        );
    }

    /**
     * Set an authorizer for the resource.
     *
     * @param string $authorizer
     * @return $this
     */
    public function authorizer(string $authorizer): self
    {
        return $this->middleware("json-api.auth:{$authorizer}");
    }

    /**
     * Add middleware.
     *
     * @param string ...$middleware
     * @return $this
     */
    public function middleware(string ...$middleware): self
    {
        $this->options['middleware'] = array_merge(
            Arr::wrap($this->options['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * @param string $controller
     * @return $this
     */
    public function controller(string $controller = ''): self
    {
        $this->options['controller'] = $controller ?: Str::classify($this->resourceType) . 'Controller';

        return $this;
    }

    /**
     * @param string $negotiator
     * @return $this
     */
    public function contentNegotiator(string $negotiator): self
    {
        $this->options['content-negotiator'] = $negotiator;

        return $this;
    }

    /**
     * @param string|null $constraint
     * @return $this
     */
    public function id(?string $constraint): self
    {
        $this->options['id'] = $constraint;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return $this
     */
    public function only(string ...$actions): self
    {
        $this->options['only'] = $actions;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return $this
     */
    public function except(string ...$actions): self
    {
        $this->options['except'] = $actions;

        return $this;
    }

    /**
     * @return $this
     */
    public function readOnly(): self
    {
        return $this->only('index', 'read');
    }

    /**
     * @param \Closure $closure
     * @return $this
     */
    public function relationships(\Closure $closure): self
    {
        $closure($this->relationships);

        return $this;
    }

    /**
     * @param string $constraint
     * @return $this
     */
    public function async(string $constraint = Uuid::VALID_PATTERN): ResourceRegistration
    {
        $this->options['async'] = true;
        $this->options['async_id'] = $constraint;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return collect($this->options)->merge($this->relationships)->all();
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registered = true;

        $group = new ResourceGroup($this->resourceType, $this->toArray());
        $group->register($this->router);
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        if (!$this->registered) {
            $this->register();
        }
    }

}
