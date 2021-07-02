<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDespachosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('despachos', function (Blueprint $table) {
            $table->id();
            $table->integer('despachable_id');
            $table->string('despachable_type');
            $table->integer('destinable_id');
            $table->string('destinable_type');
            $table->bigInteger('oc_id')->unsigned();
            $table->timestamp('fecha');
            $table->string('ndocumento')->nullable();
            $table->string('responsable');
            $table->longText('comentario')->nullable();
            $table->timestamps();

            $table->foreign('oc_id')->references('id')->on('ocs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('despachos');
    }
}
