<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDespachoOcparteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('despacho_ocparte', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('despacho_id')->unsigned();
            $table->bigInteger('ocparte_id')->unsigned();
            $table->integer('cantidad');
            $table->timestamps();

            $table->foreign('despacho_id')->references('id')->on('despachos')->onDelete('cascade');
            $table->foreign('ocparte_id')->references('id')->on('oc_parte')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('despacho_ocparte');
    }
}
