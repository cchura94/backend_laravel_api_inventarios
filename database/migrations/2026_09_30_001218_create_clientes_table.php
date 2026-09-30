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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->string("nombre_completo");
            $table->string("nro_identificacion", 30)->nullable();
            $table->date("fecha_nacimiento")->nullable();
            $table->string("telefono", 22)->nullable();
            $table->string("correo", 200)->nullable();
            $table->boolean("estado")->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
