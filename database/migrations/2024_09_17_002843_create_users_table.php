<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('user')->unique(); // Campo "user" en lugar de "email"
            $table->timestamp('user_verified_at')->nullable(); // Opcional: verificación del campo "user"
            $table->string('password');
            $table->enum('rol', ['gerente', 'coordinador',,'analista','team_member','group_leader']);
            $table->enum('turno', ['central', 'amarillo', 'azul', 'blanco']);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
