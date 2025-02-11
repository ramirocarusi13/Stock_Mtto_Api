<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('gerente_id')->nullable()->constrained('users')->onDelete('cascade'); // Gerente que aprueba
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'devolución']);
            $table->integer('cantidad');
            $table->dateTime('fecha_movimiento')->default(now());
            $table->string('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
