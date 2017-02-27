<?php

namespace CloudCreativity\LaravelJsonApi\Repositories;

use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use ReflectionClass;
use Config;

class SchemasRepository implements SchemasRepositoryInterface
{
    const JSON_API_SCHEMA = 'JSON_API_SCHEMA';

    private $factory;

    private $namespaced = false;

    private $schemas = [];

    public function __construct(SchemaFactoryInterface $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }

    public function getSchemas($name = null)
    {
        $name = ($name) ?: static::DEFAULTS;

        if (static::DEFAULTS !== $name && !$this->namespaced) {
            throw new \RuntimeException(sprintf('Schemas configuration is not namespaced, so cannot get "%s".', $name));
        }

        $defaults = $this->get(static::DEFAULTS);
        $schemas = (static::DEFAULTS === $name) ? $defaults : array_merge($defaults, $this->get($name));

        return $this->factory->createContainer($schemas);
    }

    public function configure(array $config)
    {
        if (!isset($config[static::DEFAULTS])) {
            $config = [static::DEFAULTS => $config];
            $this->namespaced = false;
        } else {
            $this->namespaced = true;
        }

        $this->schemas = $config;
        
        $automappingNamespaces = [];
        foreach(Config::get('json-api.namespaces') as $key => $value)
        {
            if(isset($value['automapping']) && $value['automapping'])
            {
                $automappingNamespaces[] = $key;
            }
        }

        if(! empty($automappingNamespaces))
        {
            $models = array_filter(glob('app/Models/*'), 'is_file');
            foreach($models as $model)
            {
                $class = '';
                foreach(explode('/', str_replace('.php', '', $model)) as $item) $class .= ucfirst($item).'\\';
                $class = substr($class, 0, strlen($class) - 1);

                if(class_exists($class))
                {
                    $jsonSchema = (new ReflectionClass($class))->getConstant(static::JSON_API_SCHEMA);
                    if($jsonSchema)
                    {
                        foreach($this->schemas as $namespace => &$mappings)
                        {
                            if(in_array($namespace, $automappingNamespaces) && ! isset($mappings[$class]))
                            {
                                $mappings[$class] = $jsonSchema;
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }

    private function get($key)
    {
        return array_key_exists($key, $this->schemas) ? (array) $this->schemas[$key] : [];
    }
}
