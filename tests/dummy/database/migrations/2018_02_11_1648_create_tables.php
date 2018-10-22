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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTables extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('author');
            $table->boolean('admin');
            $table->rememberToken();
            $table->timestamps();
            $table->unsignedInteger('country_id')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->timestamp('published_at')->nullable();
            $table->string('title');
            $table->string('slug');
            $table->text('content');
            $table->unsignedInteger('author_id')->nullable();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->uuid('uuid');
            $table->timestamps();
            $table->string('url');
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->text('content');
            $table->nullableMorphs('commentable');
            $table->unsignedInteger('user_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uuid');
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tag_id');
            $table->morphs('taggable');
        });

        Schema::create('phones', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('number');
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('name');
            $table->string('code');
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });

        // @TODO this needs to be moved to a package migration
        Schema::create('json_api_client_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->timestamps();
            $table->string('resource_type');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('phones');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('downloads');

        // @TODO remove this
        Schema::dropIfExists('json_api_client_jobs');
    }
}
