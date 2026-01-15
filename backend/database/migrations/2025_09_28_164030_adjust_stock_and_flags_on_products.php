<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'stock')) {
                $t->dropColumn('stock'); // garante que não tenha estoque duplicado
            }
        });

        Schema::table('variants', function (Blueprint $t) {
            if (!Schema::hasColumn('variants', 'stock')) {
                $t->integer('stock')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $t) {
            if (Schema::hasColumn('variants', 'stock')) {
                $t->dropColumn('stock');
            }
        });
    }
};
