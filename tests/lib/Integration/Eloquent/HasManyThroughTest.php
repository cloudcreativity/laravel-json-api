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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Country;
use DummyApp\Post;
use DummyApp\User;

/**
 * Class HasManyTest
 *
 * Test a JSON API has-many relationship that relates to an Eloquent
 * has-many-through relationship.
 *
 * In our dummy app, this is the posts relationship on a country model.
 *
 * This relationship is read-only because it does not make sense to
 * modify the relationship through the resource relationship. For example,
 * if adding a post to the country's posts relationship, the post must
 * have a user on which to update the country id. Although the request
 * has asked to modify country->post, what it would actually do is associate
 * a country to a user. So this kind of update would not be logical: it
 * makes more sense for the client to submit a request asking a user
 * to be associated to a country.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasManyThroughTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'countries';

    public function testReadRelated()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();
        $users = factory(User::class, 2)->create([
            'country_id' => $country->getKey(),
        ]);

        $post1 = factory(Post::class)->create([
            'author_id' => $users->first()->getKey(),
        ]);

        $post2 = factory(Post::class)->create([
            'author_id' => $users->last()->getKey(),
        ]);

        $this->doReadRelated($country, 'posts')
            ->assertReadHasMany('posts', [$post1, $post2]);
    }

    public function testReadRelatedEmpty()
    {
        /** @var Country $country */
        $country = factory(Country::class)->create();

        $this->doReadRelated($country, 'posts')
            ->assertReadHasMany(null);
    }

    public function testReadRelationship()
    {
        $country = factory(Country::class)->create();
        $user = factory(User::class)->create([
            'country_id' => $country->getKey(),
        ]);

        $posts = factory(Post::class, 3)->create([
            'author_id' => $user->getKey(),
        ]);

        $this->doReadRelationship($country, 'posts')
            ->assertReadHasManyIdentifiers('posts', $posts);
    }

    public function testReadEmptyRelationship()
    {
        $country = factory(Country::class)->create();

        $this->doReadRelationship($country, 'users')
            ->assertReadHasManyIdentifiers(null);
    }

}
