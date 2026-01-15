<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('buyer_postal_code', 9)->nullable()->after('buyer_phone');
            $table->string('buyer_address', 255)->nullable()->after('buyer_postal_code');
            $table->string('buyer_address_number', 10)->nullable()->after('buyer_address');
            $table->string('buyer_address_complement', 100)->nullable()->after('buyer_address_number');
            $table->string('buyer_province', 100)->nullable()->after('buyer_address_complement');
            $table->string('buyer_city', 100)->nullable()->after('buyer_province');
            $table->string('buyer_state', 2)->nullable()->after('buyer_city');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_postal_code',
                'buyer_address',
                'buyer_address_number',
                'buyer_address_complement',
                'buyer_province',
                'buyer_city',
                'buyer_state',
            ]);
        });
    }
};
