<?php

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Ramsey\Uuid\Uuid;

class ResourceRegistration implements Arrayable
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
     * @var array
     */
    private $hasOne;

    /**
     * @var array
     */
    private $hasMany;

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
        $this->options = collect($options)->forget(['has-one', 'has-many'])->all();
        $this->hasOne = $this->normalizeRelationships($options['has-one'] ?? null);
        $this->hasMany = $this->normalizeRelationships($options['has-many'] ?? null);
        $this->registered = false;
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
        $this->options['controller'] = $controller ?: true;

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
     * @return ResourceRegistration
     */
    public function id(?string $constraint): self
    {
        $this->options['id'] = $constraint;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return ResourceRegistration
     */
    public function only(string ...$actions): self
    {
        $this->options['only'] = $actions;

        return $this;
    }

    /**
     * @param string ...$actions
     * @return ResourceRegistration
     */
    public function except(string ...$actions): self
    {
        $this->options['except'] = $actions;

        return $this;
    }

    /**
     * @return ResourceRegistration
     */
    public function readOnly(): self
    {
        return $this->only('index', 'read');
    }

    /**
     * @param string $field
     * @param string|null $inverse
     * @return RelationshipRegistration
     */
    public function hasOne(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasOne[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasOne[$field] = $rel;
    }

    /**
     * @param string $field
     * @param string|null $inverse
     * @return RelationshipRegistration
     */
    public function hasMany(string $field, string $inverse = null): RelationshipRegistration
    {
        $rel = $this->hasMany[$field] ?? new RelationshipRegistration();

        if ($inverse) {
            $rel->inverse($inverse);
        }

        return $this->hasMany[$field] = $rel;
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
        $options = $this->options;
        $options['has-one'] = collect($this->hasOne)->toArray();
        $options['has-many'] = collect($this->hasMany)->toArray();

        return $options;
    }

    /**
     * @return void
     */
    public function register(): void
    {
        $this->registered = true;

        $group = new ResourceGroup($this->resourceType, new Fluent($this->toArray()));
        $group->addResource($this->router);
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

    /**
     * @param $value
     * @return array
     */
    private function normalizeRelationships($value): array
    {
        return collect(Arr::wrap($value ?: []))->mapWithKeys(function ($value, $key) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            return [$key => new RelationshipRegistration(Arr::wrap($value))];
        })->all();
    }

}
