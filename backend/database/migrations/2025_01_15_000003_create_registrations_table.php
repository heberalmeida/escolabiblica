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
        Schema::create('registrations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('event_id')->constrained()->onDelete('cascade');
            $t->string('registration_number')->unique(); // Número aleatório da inscrição
            $t->string('qr_code')->unique(); // Código único para QR code
            
            // Dados do inscrito
            $t->string('name');
            $t->string('phone');
            $t->date('birth_date');
            $t->string('sector')->nullable(); // Setor
            $t->string('congregation')->nullable(); // Congregação
            $t->string('church_type')->nullable(); // 'outra_igreja', 'membro', 'diacono', 'diaconisa', 'presbitero', 'missionaria', 'evangelista', 'pastor', 'missionario', 'visitante'
            $t->enum('gender', ['MASCULINO', 'FEMININO']);
            $t->boolean('whatsapp_authorization')->default(false);
            
            // Pagamento
            $t->integer('price_paid')->default(0); // Valor pago em centavos
            $t->string('payment_method')->nullable(); // 'PIX', 'BOLETO', 'CREDIT_CARD', 'FREE'
            $t->string('payment_status')->default('pending'); // 'pending', 'paid', 'canceled', 'failed'
            $t->string('asaas_customer_id')->nullable();
            $t->string('asaas_payment_id')->nullable();
            $t->json('gateway_payload')->nullable();
            
            // Validação
            $t->boolean('validated')->default(false);
            $t->timestamp('validated_at')->nullable();
            $t->string('validated_by')->nullable(); // Nome ou método de validação
            
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
