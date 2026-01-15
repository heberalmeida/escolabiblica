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
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->string('name'); // "35 EBCG - Escola Bíblica de Campo Grande 2026"
            $t->text('description')->nullable(); // "Tempo de Conquista Josué 1:3"
            $t->date('start_date'); // Data de início
            $t->date('end_date'); // Data de término
            $t->string('image')->nullable(); // Imagem do evento
            $t->integer('price')->default(0); // Preço em centavos (0 = gratuito)
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
