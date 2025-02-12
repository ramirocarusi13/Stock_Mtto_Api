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

            // Relación con inventarios basada en código
            $table->string('codigo_producto');
            $table->foreign('codigo_producto')->references('codigo')->on('inventarios')->onDelete('cascade');

            // Relación con usuarios
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade'); // Usuario que creó el movimiento
            $table->foreignId('user_aprobacion_id')->nullable()->constrained('users')->onDelete('set null'); // Usuario que aprobó/rechazó el movimiento

            // Estado del movimiento
            $table->enum('estado', ['aprobado', 'pendiente', 'rechazado']);
            $table->integer('cantidad');
            $table->dateTime('fecha_movimiento')->default(now());
            $table->enum('motivo', ['ingreso', 'egreso', 'prestamo', 'devolucion']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};
