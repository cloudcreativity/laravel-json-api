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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientJobsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('json_api_client_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->timestamps();
            $table->string('api');
            $table->string('resource_type');
            $table->string('resource_id')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('timeout')->nullable();
            $table->timestamp('timeout_at')->nullable();
            $table->integer('tries')->nullable();
            $table->boolean('failed')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('json_api_client_jobs');
    }
}
