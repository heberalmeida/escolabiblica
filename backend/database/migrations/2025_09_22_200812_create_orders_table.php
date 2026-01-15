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
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('order_number')->unique();
            $t->string('buyer_name');
            $t->string('buyer_cpf');
            $t->string('buyer_email')->nullable();
            $t->string('buyer_phone')->nullable();
            $t->foreignId('sector_id')->constrained(); // onde vai retirar
            $t->integer('total_amount'); // centavos
            $t->string('status')->default('pending'); // pending, paid, canceled, failed
            $t->string('asaas_customer_id')->nullable();
            $t->string('asaas_payment_id')->nullable();
            $t->string('payment_method'); // 'BOLETO', 'CREDIT_CARD', 'PIX'
            $t->json('gateway_payload')->nullable(); // links/qrCode etc
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
