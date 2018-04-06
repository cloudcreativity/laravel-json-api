<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackageTables extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('title');
            $table->text('article');
            $table->timestamp('published_at');
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('blogs');
    }
}
