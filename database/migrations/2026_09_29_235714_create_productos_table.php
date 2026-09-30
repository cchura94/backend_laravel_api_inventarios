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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string("nombre", 200);
            $table->text("descripcion")->nullable();
            $table->string("codigo_barra", 100)->nullable();
            $table->string("unidad_medida", 50)->nullable();
            $table->string("marca", 100)->nullable();
            $table->bigInteger("categoria_id")->unsigned();
            $table->decimal("precio_venta_actual", 12, 2)->default(0);
            $table->integer("stock_minimo")->default(1);
            $table->string("imagen", 255)->nullable();
            $table->boolean("estado")->default(true);
            $table->dateTime("fecha_registro")->nullable();
            
            $table->foreign("categoria_id")->references("id")->on("categorias");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
