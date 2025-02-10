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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id(); // ID único para el proveedor
            $table->string('nombre')->unique(); // Nombre del proveedor (único)
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }


    /**
     * Reverse the migrations.
     */
};
