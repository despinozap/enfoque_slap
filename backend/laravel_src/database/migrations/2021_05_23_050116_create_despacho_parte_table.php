<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDespachoParteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('despacho_parte', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('parte_id')->unsigned();
            $table->bigInteger('despacho_id')->unsigned();
            $table->integer('cantidad');
            $table->timestamps();

            $table->foreign('parte_id')->references('id')->on('partes')->onDelete('cascade');
            $table->foreign('despacho_id')->references('id')->on('despachos')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('despacho_parte');
    }
}
