<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Evita erro se a coluna já existir
            if (!Schema::hasColumn('variants', 'active')) {
                $table->boolean('active')->default(true)->after('stock');
            }
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            if (Schema::hasColumn('variants', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};
