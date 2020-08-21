<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParConfirmationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('par_confirmations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('flag');
            $table->string('name');
            $table->string('description')->nullable(true);
            $table->dateTime('created_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('created_by')->nullable(true);
            $table->dateTime('dola')->nullable(true);
            $table->integer('altered_by')->nullable(true);
            $table->integer('is_enabled')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('par_confirmations');
    }
}
