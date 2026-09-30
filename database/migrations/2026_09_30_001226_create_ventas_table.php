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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();

            $table->string("codigo", 30)->nullable();
            $table->dateTime("fecha");
            $table->bigInteger("cliente_id")->unsigned();
            $table->bigInteger("user_id")->unsigned();
            $table->decimal("descuento_total", 12, 2)->nullable();
            $table->string("estado", 30)->nullable();
            $table->text("detalle")->nullable();
            $table->text("observaciones")->nullable();

            $table->foreign("cliente_id")->references("id")->on("clientes");
            $table->foreign("user_id")->references("id")->on("users");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
