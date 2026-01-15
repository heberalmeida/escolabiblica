<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('variant_color')->nullable();
            $table->string('variant_fit')->nullable();
            $table->string('variant_size')->nullable();
            $table->json('variant_data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'variant_color',
                'variant_fit',
                'variant_size',
                'variant_data',
            ]);
        });
    }
};
