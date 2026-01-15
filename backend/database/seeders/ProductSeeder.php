<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Variant;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::create([
            'name'        => 'Camiseta Heaven',
            'description' => 'Camiseta oficial com várias cores e tamanhos',
            'base_price'  => 4990, // em centavos = R$49,90
            'active'      => true,
        ]);

        $colors = ['Preto', 'Vermelho'];
        $fits   = ['Normal', 'Baby Look'];
        $sizes  = ['PP','P','M','G','GG','XGG','XXGG'];

        foreach ($colors as $color) {
            foreach ($fits as $fit) {
                foreach ($sizes as $size) {
                    Variant::create([
                        'product_id'     => $product->id,
                        'color'          => $color,
                        'fit'            => $fit,
                        'size'           => $size,
                        'price_override' => null, // usa base_price
                    ]);
                }
            }
        }
    }
}
