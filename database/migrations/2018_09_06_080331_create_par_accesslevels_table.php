<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateParAccesslevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('par_accesslevels', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable(true);
            $table->dateTime('created_on')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->integer('created_by')->nullable(true);
            $table->dateTime('dola')->nullable(true);
            $table->integer('altered_by')->nullable(true);
            $table->integer('is_enabled')->default(1);
            //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('par_accesslevels');
    }
}
