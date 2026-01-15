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
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('name');
            $t->string('cpf')->unique();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->foreignId('sector_id')->nullable()->constrained();
            $t->timestamp('email_verified_at')->nullable(); // 🔹 adicionado
            $t->string('password')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
