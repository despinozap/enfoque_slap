<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOcparteRecepcionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ocparte_recepcion', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('ocparte_id')->unsigned();
            $table->bigInteger('recepcion_id')->unsigned();
            $table->integer('cantidad');
            $table->longText('comentario')->nullable();


            $table->timestamps();

            $table->foreign('ocparte_id')->references('id')->on('oc_parte')->onDelete('cascade');
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
        Schema::dropIfExists('ocparte_recepcion');
    }
}
