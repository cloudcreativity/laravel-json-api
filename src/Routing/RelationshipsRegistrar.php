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

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Class RelationshipsRegistrar
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class RelationshipsRegistrar implements \IteratorAggregate
{

    private const METHODS = [
        'related' => 'get',
        'read' => 'get',
        'replace' => 'patch',
        'add' => 'post',
        'remove' => 'delete',
    ];

    private const CONTROLLER_ACTIONS = [
        'related' => 'readRelatedResource',
        'read' => 'readRelationship',
        'replace' => 'replaceRelationship',
        'add' => 'addToRelationship',
        'remove' => 'removeFromRelationship',
    ];

    use RegistersResources;

    /**
     * RelationshipsRegistrar constructor.
     *
     * @param Registrar $router
     * @param string $resourceType
     * @param array $options
     */
    public function __construct(Registrar $router, string $resourceType, array $options = [])
    {
        $this->router = $router;
        $this->resourceType = $resourceType;
        $this->options = $options;
    }

    /**
     * @return void
     */
    public function register(): void
    {
        foreach ($this as $relationship => $options) {
            $this->add($relationship, $options);
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        foreach ($this->hasOne() as $hasOne => $options) {
            $options['actions'] = $this->hasOneActions($options);
            yield $hasOne => $options;
        }

        foreach ($this->hasMany() as $hasMany => $options) {
            $options['actions'] = $this->hasManyActions($options);
            yield $hasMany => $options;
        }
    }

    /**
     * @return array
     */
    private function hasOne(): array
    {
        return $this->options['has-one'] ?? [];
    }

    /**
     * @return array
     */
    private function hasMany(): array
    {
        return $this->options['has-many'] ?? [];
    }

    /**
     * @param string $field
     * @param array $options
     * @return void
     */
    private function add(string $field, array $options): void
    {
        $inverse = $options['inverse'] ?? Str::plural($field);

        $this->router->group([], function () use ($field, $options, $inverse) {
            foreach ($options['actions'] as $action) {
                $this->route($field, $action, $inverse, $options);
            }
        });
    }

    /**
     * @param string $field
     * @param string $action
     * @param string $inverse
     *      the inverse resource type
     * @param array $options
     * @return Route
     */
    private function route(string $field, string $action, string $inverse, array $options): Route
    {
        $route = $this->createRoute(
            $this->methodForAction($action),
            $this->urlForAction($field, $action, $options),
            $this->actionForRoute($field, $action)
        );

        $route->defaults(ResourceRegistrar::PARAM_RELATIONSHIP_NAME, $field);
        $route->defaults(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE, $inverse);

        return $route;
    }

    /**
     * @param array $options
     * @return array
     */
    private function hasOneActions(array $options): array
    {
        return $this->diffActions(['related', 'read', 'replace'], $options);
    }

    /**
     * @param array $options
     * @return array
     */
    private function hasManyActions(array $options): array
    {
        return $this->diffActions(['related', 'read', 'replace', 'add', 'remove'], $options);
    }

    /**
     * @param string $relationship
     * @param array $options
     * @return string
     */
    private function relatedUrl(string $relationship, array $options): string
    {
        return sprintf(
            '%s/%s',
            $this->resourceUrl(),
            $options['relationship_uri'] ?? $relationship
        );
    }

    /**
     * @param string $relationship
     * @param array $options
     * @return string
     */
    private function relationshipUrl(string $relationship, array $options): string
    {
        return sprintf(
            '%s/%s/%s',
            $this->resourceUrl(),
            ResourceRegistrar::KEYWORD_RELATIONSHIPS,
            $options['relationship_uri'] ?? $relationship
        );
    }

    /**
     * @param string $action
     * @return string
     */
    private function methodForAction(string $action): string
    {
        return self::METHODS[$action];
    }

    /**
     * @param string $field
     * @param string $action
     * @param array $options
     * @return string
     */
    private function urlForAction(string $field, string $action, array $options): string
    {
        if ('related' === $action) {
            return $this->relatedUrl($field, $options);
        }

        return $this->relationshipUrl($field, $options);
    }

    /**
     * @param string $field
     * @param string $action
     * @return string
     */
    private function nameForAction(string $field, string $action): string
    {
        $name = "relationships.{$field}";

        if ('related' !== $action) {
            $name .= ".{$action}";
        }

        return $name;
    }

    /**
     * @param string $field
     * @param string $action
     * @return array
     */
    private function actionForRoute(string $field, string $action): array
    {
        return [
            'as' => $this->nameForAction($field, $action),
            'uses' => $this->controllerAction($action),
        ];
    }

    /**
     * @param string $action
     * @return string
     */
    private function controllerAction(string $action): string
    {
        return sprintf('%s@%s', $this->controller(), self::CONTROLLER_ACTIONS[$action]);
    }
}
