<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProveedorRecepcionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proveedor_recepcion', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proveedor_id')->unsigned();
            $table->bigInteger('recepcion_id')->unsigned();
            
            $table->timestamps();
            
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
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
        Schema::dropIfExists('proveedor_recepcion');
    }
}
