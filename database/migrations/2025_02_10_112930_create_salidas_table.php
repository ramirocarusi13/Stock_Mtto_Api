<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventario_id');
            $table->unsignedBigInteger('usuario_id');
            $table->integer('cantidad');
            $table->string('motivo');
            $table->timestamps();

            $table->foreign('inventario_id')->references('id')->on('inventarios')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('salidas');
    }
};
