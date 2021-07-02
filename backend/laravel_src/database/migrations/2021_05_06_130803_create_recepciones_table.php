<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecepcionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->integer('recepcionable_id');
            $table->string('recepcionable_type');
            $table->integer('sourceable_id');
            $table->string('sourceable_type');
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
        Schema::dropIfExists('recepciones');
    }
}
