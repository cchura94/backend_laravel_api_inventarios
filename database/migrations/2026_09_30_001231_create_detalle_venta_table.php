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
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();

            $table->bigInteger("venta_id")->unsigned();
            $table->bigInteger("producto_id")->unsigned();
            $table->bigInteger("almacen_id")->unsigned();
            $table->integer("cantidad")->default(1);
            $table->decimal("precio_unitario_venta", 12, 2)->default(0);
            $table->text("observaciones")->nullable();

            $table->foreign("venta_id")->references("id")->on("ventas");
            $table->foreign("producto_id")->references("id")->on("productos");
            $table->foreign("almacen_id")->references("id")->on("almacens");

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};
