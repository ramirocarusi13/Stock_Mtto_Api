<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dateTime('fecha_punto_pedido')->nullable()->after('punto_de_pedido');
        });
    }

    public function down()
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropColumn('fecha_punto_pedido');
        });
    }
};
