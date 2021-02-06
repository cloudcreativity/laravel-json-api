<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\CreateResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\DeleteResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchRelated;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchRelationship;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResources;
use CloudCreativity\LaravelJsonApi\Http\Requests\UpdateRelationship;
use CloudCreativity\LaravelJsonApi\Http\Requests\UpdateResource;
use DummyApp\Post;
use DummyApp\User;
use PHPUnit\Framework\Assert;

class TestController extends JsonApiController
{

    /**
     * @param FetchResources $request
     */
    public function searching(FetchResources $request)
    {
        event(new TestEvent('searching', null, $request));
    }

    /**
     * @param $results
     * @param FetchResources $request
     */
    public function searched($results, FetchResources $request)
    {
        event(new TestEvent('searched', $results, $request));
    }

    /**
     * @param Post $record
     * @param FetchResource $request
     */
    public function reading(Post $record, FetchResource $request)
    {
        event(new TestEvent('reading', $record, $request));
    }

    /**
     * @param Post|null $record
     * @param FetchResource $request
     */
    public function didRead(?Post $record, FetchResource $request)
    {
        event(new TestEvent('did-read', $record, $request));
    }

    /**
     * @param $record
     * @param CreateResource|UpdateResource $request
     */
    public function saving($record, $request)
    {
        if (!$request instanceof CreateResource && !$request instanceof UpdateResource) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('saving', $record, $request));
    }

    /**
     * @param CreateResource $request
     */
    public function creating(CreateResource $request)
    {
        event(new TestEvent('creating', null, $request));
    }

    /**
     * @param Post $record
     * @param UpdateResource $request
     */
    public function updating(Post $record, UpdateResource $request)
    {
        event(new TestEvent('updating', $record, $request));
    }

    /**
     * @param Post $record
     * @param CreateResource|UpdateResource $request
     */
    public function saved(Post $record, $request)
    {
        if (!$request instanceof CreateResource && !$request instanceof UpdateResource) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('saved', $record, $request));
    }

    /**
     * @param Post $record
     * @param CreateResource $request
     */
    public function created(Post $record, CreateResource $request)
    {
        event(new TestEvent('created', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateResource $request
     */
    public function updated(Post $record, UpdateResource $request)
    {
        event(new TestEvent('updated', $record, $request));
    }

    /**
     * @param Post $record
     * @param DeleteResource $request
     */
    public function deleting(Post $record, DeleteResource $request)
    {
        event(new TestEvent('deleting', $record, $request));
    }

    /**
     * @param Post $record
     * @param DeleteResource $request
     */
    public function deleted(Post $record, DeleteResource $request)
    {
        event(new TestEvent('deleted', $record->getKey(), $request));
    }

    /**
     * @param Post $record
     * @param $request
     */
    public function readingRelationship(Post $record, $request)
    {
        if (!$request instanceof FetchRelated && !$request instanceof FetchRelationship) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('reading-relationship', $record, $request));
    }

    /**
     * @param Post $record
     * @param $request
     */
    public function readingAuthor(Post $record, $request)
    {
        if (!$request instanceof FetchRelated && !$request instanceof FetchRelationship) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('reading-author', $record, $request));
    }

    /**
     * @param Post $record
     * @param User|null $user
     * @param $request
     */
    public function didReadAuthor(Post $record, ?User $user, $request)
    {
        if (!$request instanceof FetchRelated && !$request instanceof FetchRelationship) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('did-read-author', $record, $request, $user));
    }

    /**
     * @param Post $record
     * @param mixed|null $related
     * @param $request
     */
    public function didReadRelationship(Post $record, $related, $request)
    {
        if (!$request instanceof FetchRelated && !$request instanceof FetchRelationship) {
            Assert::fail('Invalid request class.');
        }

        event(new TestEvent('did-read-relationship', $record, $request, $related));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function replacing(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('replacing', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function replacingAuthor(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('replacing-author', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function replaced(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('replaced', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function replacedAuthor(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('replaced-author', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function adding(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('adding', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function addingTags(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('adding-tags', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function addedTags(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('added-tags', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function added(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('added', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function removing(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('removing', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function removingTags(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('removing-tags', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function removedTags(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('removed-tags', $record, $request));
    }

    /**
     * @param Post $record
     * @param UpdateRelationship $request
     */
    public function removed(Post $record, UpdateRelationship $request)
    {
        event(new TestEvent('removed', $record, $request));
    }

}
