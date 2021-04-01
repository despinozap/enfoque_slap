<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCotizacionParteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cotizacion_parte', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parte_id')->unsigned();
            $table->bigInteger('cotizacion_id')->unsigned();
            $table->longText('descripcion')->nullable();
            $table->integer('cantidad');
            $table->float('costo')->nullable();
            $table->integer('tiempoentrega')->nullable();
            $table->float('margen')->nullable();
            $table->float('peso')->nullable();
            $table->float('flete')->nullable();
            $table->double('monto', 10, 2)->nullable();
            $table->boolean('backorder')->default(false);
            $table->timestamps();

            $table->foreign('parte_id')->references('id')->on('partes')->onDelete('cascade');
            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cotizacion_parte');
    }
}
