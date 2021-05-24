<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntregaOcparteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entrega_ocparte', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('entrega_id')->unsigned();
            $table->bigInteger('ocparte_id')->unsigned();
            $table->integer('cantidad');
            $table->timestamps();

            $table->foreign('entrega_id')->references('id')->on('entregas')->onDelete('cascade');
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
        Schema::dropIfExists('entrega_ocparte');
    }
}
