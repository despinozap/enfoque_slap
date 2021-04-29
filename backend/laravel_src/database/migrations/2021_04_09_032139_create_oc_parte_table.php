<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOcParteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('oc_parte', function (Blueprint $table) {          
            $table->id();
            $table->bigInteger('oc_id')->unsigned();
            $table->bigInteger('parte_id')->unsigned();
            $table->bigInteger('estadoocparte_id')->unsigned();
            $table->longText('descripcion')->nullable();
            $table->integer('cantidad');
            $table->integer('cantidadpendiente');
            $table->integer('cantidadasignado')->default(0);
            $table->integer('cantidaddespachado')->default(0);
            $table->integer('cantidadrecibido')->default(0);
            $table->integer('cantidadentregado')->default(0);
            $table->integer('tiempoentrega');
            $table->boolean('backorder');


            $table->timestamps();

            $table->foreign('oc_id')->references('id')->on('ocs')->onDelete('cascade');
            $table->foreign('parte_id')->references('id')->on('partes')->onDelete('cascade');
            $table->foreign('estadoocparte_id')->references('id')->on('estadoocpartes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('oc_parte');
    }
}
