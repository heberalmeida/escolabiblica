<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ⚠️ Evita recriar colunas já existentes
        Schema::table('products', function (Blueprint $t) {
            if (!Schema::hasColumn('products', 'old_price')) {
                $t->integer('old_price')->nullable();
            }
            if (!Schema::hasColumn('products', 'infinite_stock')) {
                $t->boolean('infinite_stock')->default(true);
            }
        });

        Schema::table('variants', function (Blueprint $t) {
            if (!Schema::hasColumn('variants', 'stock')) {
                $t->integer('stock')->nullable();
            }
            if (!Schema::hasColumn('variants', 'size_description')) {
                $t->text('size_description')->nullable();
            }
        });

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $t) {
                $t->id();
                $t->foreignId('product_id')->constrained()->onDelete('cascade');
                $t->string('url');
                $t->integer('position')->default(0);
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_images')) {
            Schema::dropIfExists('product_images');
        }

        Schema::table('variants', function (Blueprint $t) {
            if (Schema::hasColumn('variants', 'stock')) {
                $t->dropColumn('stock');
            }
            if (Schema::hasColumn('variants', 'size_description')) {
                $t->dropColumn('size_description');
            }
        });

        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'old_price')) {
                $t->dropColumn('old_price');
            }
            if (Schema::hasColumn('products', 'infinite_stock')) {
                $t->dropColumn('infinite_stock');
            }
        });
    }
};
