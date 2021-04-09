<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ocs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('cotizacion_id')->unsigned();
            $table->bigInteger('proveedor_id')->unsigned()->nullable()->default(null);
            $table->bigInteger('estadooc_id')->unsigned();
            $table->string('noccliente');
            $table->float('usdvalue');

            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
            $table->foreign('estadooc_id')->references('id')->on('estadoocs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ocs');
    }
}
