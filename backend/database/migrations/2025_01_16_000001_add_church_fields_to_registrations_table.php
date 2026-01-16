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
        Schema::table('registrations', function (Blueprint $table) {
            $table->enum('church_affiliation', ['ASSEMBLEIA', 'OUTRA_IGREJA', 'NAO_EVANGELICO'])->nullable()->after('cpf');
            $table->string('other_church_name')->nullable()->after('church_affiliation'); // Nome da igreja quando for "De outra igreja"
            $table->string('position')->nullable()->after('church_type'); // Campo para cargo personalizado
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn(['church_affiliation', 'other_church_name', 'position']);
        });
    }
};
