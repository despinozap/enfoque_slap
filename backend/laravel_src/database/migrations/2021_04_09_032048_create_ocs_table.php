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
            $table->bigInteger('filedata_id')->unsigned()->nullable()->default(null); //Document OC cliente
            $table->bigInteger('estadooc_id')->unsigned();
            $table->string('noccliente');
            $table->bigInteger('motivobaja_id')->unsigned()->nullable()->default(null);
            $table->float('usdvalue');
            

            $table->timestamps();

            $table->foreign('cotizacion_id')->references('id')->on('cotizaciones')->onDelete('cascade');
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
            $table->foreign('filedata_id')->references('id')->on('filedatas')->onDelete('cascade');
            $table->foreign('estadooc_id')->references('id')->on('estadoocs')->onDelete('cascade');
            $table->foreign('motivobaja_id')->references('id')->on('motivosbaja')->onDelete('cascade');
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
