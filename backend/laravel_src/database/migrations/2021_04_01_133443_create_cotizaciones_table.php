<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCotizacionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('solicitud_id')->unsigned();
            $table->bigInteger('estadocotizacion_id')->unsigned();
            $table->bigInteger('motivorechazo_id')->unsigned()->nullable()->default(null);
            $table->float('usdvalue');
            $table->dateTime('lastupdate'); // Stores last date when USD and flete (partes) values were updated

            $table->timestamps();

            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('estadocotizacion_id')->references('id')->on('estadocotizaciones')->onDelete('cascade');
            $table->foreign('motivorechazo_id')->references('id')->on('motivosrechazo')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cotizaciones');
    }
}
