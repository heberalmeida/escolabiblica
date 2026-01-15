<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventPaymentMethod;
use App\Models\Product;
use App\Models\Variant;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpar produtos e variantes existentes
        Schema::disableForeignKeyConstraints();
        
        OrderItem::truncate();
        Variant::truncate();
        Product::truncate();
        
        Schema::enableForeignKeyConstraints();

        // Criar evento da Escola Bíblica
        $event = Event::create([
            'name' => '35 EBCG - Escola Bíblica de Campo Grande 2026',
            'description' => 'Tempo de Conquista Josué 1:3',
            'start_date' => '2026-04-02',
            'end_date' => '2026-04-05',
            'image' => null, // Pode adicionar imagem depois
            'price' => 6000, // R$ 60,00 (em centavos)
            'active' => true,
        ]);

        // Adicionar métodos de pagamento
        // FREE pode ser usado para isenções especiais
        EventPaymentMethod::create([
            'event_id' => $event->id,
            'method' => 'FREE',
            'active' => true,
        ]);

        // Métodos de pagamento: PIX, BOLETO e CREDIT_CARD
        EventPaymentMethod::create([
            'event_id' => $event->id,
            'method' => 'PIX',
            'active' => true,
        ]);

        EventPaymentMethod::create([
            'event_id' => $event->id,
            'method' => 'BOLETO',
            'active' => true,
        ]);

        EventPaymentMethod::create([
            'event_id' => $event->id,
            'method' => 'CREDIT_CARD',
            'active' => true,
        ]);

        $this->command->info('Evento "35 EBCG - Escola Bíblica de Campo Grande 2026" criado com sucesso!');
        $this->command->info('Produtos antigos foram removidos.');
    }
}
