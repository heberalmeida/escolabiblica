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
        Schema::create('event_payment_methods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->onDelete('cascade');
            $t->string('method'); // 'PIX', 'BOLETO', 'CREDIT_CARD', 'FREE'
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_payment_methods');
    }
};
