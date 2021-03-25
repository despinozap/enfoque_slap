<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSolicitudesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('faena_id')->unsigned();
            $table->bigInteger('marca_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('estadosolicitud_id')->unsigned();
            $table->longText('comentario')->nullable();
            $table->timestamps();

            $table->foreign('faena_id')->references('id')->on('faenas')->onDelete('cascade');
            $table->foreign('marca_id')->references('id')->on('marcas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('estadosolicitud_id')->references('id')->on('estadosolicitudes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('solicitudes');
    }
}
