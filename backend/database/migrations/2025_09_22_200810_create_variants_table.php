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
        Schema::create('variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->string('color'); // "preto", "vermelho"
            $t->string('fit');   // "normal", "baby look"
            $t->string('size');  // "PP","P","M","G","GG","XGG","XXGG"
            $t->integer('price_override')->nullable(); // em centavos (se diferente do base)
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
