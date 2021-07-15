<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProveedoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('comprador_id')->unsigned();
            $table->string('rut');
            $table->string('name');
            $table->string('address');
            $table->string('city');
            $table->string('email');
            $table->string('phone');
            $table->boolean('delivered');
            $table->string('delivery_name')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_email')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->timestamps();

            $table->foreign('comprador_id')->references('id')->on('compradores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proveedores');
    }
}
