<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id(); // ID único
            $table->string('codigo')->unique(); // Código único para inventario
            $table->string('descripcion'); // Descripción del ítem
            $table->unsignedBigInteger('proveedor_id')->nullable(); // Relación con proveedores
            $table->decimal('costo_proveedor_usd', 10, 2)->nullable(); // Costo del proveedor en USD
            $table->decimal('gastos_importacion_ars', 10, 2)->nullable(); // Gastos de importación en ARS
            $table->integer('minimo')->nullable(); // Cantidad mínima permitida
            $table->integer('punto_de_pedido')->nullable(); // Punto de pedido
            $table->integer('maximo')->nullable(); // Cantidad máxima permitida
            $table->integer('en_stock')->default(0); // Cantidad actual en stock
            $table->integer('entradas')->default(0); // Total de entradas
            $table->integer('salidas')->default(0); // Total de salidas
            $table->date('fecha')->nullable(); // Fecha de entrada o salida
            $table->string('sector')->nullable(); // Sector asociado a la operación
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');

            $table->enum('linea', [
                'M1', 'M2', 'M3', 'M4', 'M5', 'M6', 'M7', 'M8', 'M9', 'M10', 
                'M11', 'Mesa tendido', 'Dojo', 'Corte Electronico', 'S10', 
                'S345', 'SS1', 'SS2', 'SS4'
            ])->nullable(); // Línea asociada
            $table->string('maquina')->nullable(); // Máquina asociada
            $table->unsignedBigInteger('responsable_id')->nullable(); // Responsable de la operación
            $table->enum('turno', ['blanco', 'azul', 'amarillo'])->nullable(); // Turno asociado
            $table->decimal('costo_usd_consumido', 10, 2)->nullable(); // Costo en USD consumido en salidas

            // Campos para préstamos
            $table->boolean('prestado')->default(false); // Indica si el ítem fue prestado
            $table->date('fecha_prestado')->nullable(); // Fecha en que se prestó el ítem
            $table->string('receptor_prestamo')->nullable(); // Nombre del receptor del préstamo
            $table->integer('cantidad_prestada')->default(0); // Cantidad prestada

            $table->timestamps(); // Timestamps para created_at y updated_at

            // Llaves foráneas
            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null'); // Relación con proveedores
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('cascade'); // Relación con usuarios
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventarios');
    }
};
