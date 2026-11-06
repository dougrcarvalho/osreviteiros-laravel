<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();      // gratis | basic | plus | premium
            $table->string('wp_role')->nullable(); // role equivalente no WordPress (importação)
            $table->boolean('pago')->default(false);
            $table->string('checkout_url')->nullable(); // Hotmart ou Eduzz
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
