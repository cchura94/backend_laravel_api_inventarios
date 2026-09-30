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
        Schema::create('proveedors', function (Blueprint $table) {
            $table->id();

            $table->string("razon_social");
            $table->string("nro_identificacion", 30)->nullable();
            $table->string("contacto", 100)->nullable();
            $table->string("telefono", 22)->nullable();
            $table->string("correo", 200)->nullable();
            $table->text("observaciones")->nullable();
            $table->boolean("estado")->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedors');
    }
};
