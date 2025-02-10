<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->constrained()->onDelete('cascade');
            $table->string('receptor_prestamo');
            $table->integer('cantidad_prestada');
            $table->string('usuario_prestamo')->nullable(); // 🔹 Asegúrate de que esta línea exista
            $table->dateTime('fecha_prestado');
            $table->boolean('devuelto')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('prestamos');
    }
};
