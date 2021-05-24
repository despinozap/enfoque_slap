<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParteRecepcionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parte_recepcion', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parte_id')->unsigned();
            $table->bigInteger('recepcion_id')->unsigned();
            $table->integer('cantidad');
            $table->timestamps();

            $table->foreign('parte_id')->references('id')->on('partes')->onDelete('cascade');
            $table->foreign('recepcion_id')->references('id')->on('recepciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parte_recepcion');
    }
}
