<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParteSolicitudTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parte_solicitud', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parte_id')->unsigned();
            $table->bigInteger('solicitud_id')->unsigned();
            $table->integer('cantidad');
            $table->longText('descripcion')->nullable();
            $table->float('costo')->nullable();
            $table->float('peso')->nullable();
            $table->float('flete')->nullable();
            $table->float('margen')->nullable();
            $table->float('monto')->nullable();
            $table->integer('plazoentrega')->nullable();
            $table->timestamps();

            $table->foreign('parte_id')->references('id')->on('partes')->onDelete('cascade');
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parte_solicitud');
    }
}
