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
        Schema::table('historial_clinico', function (Blueprint $table) {
            $table->json('odontograma_state')->nullable();
            $table->longText('odontograma_image')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_clinico', function (Blueprint $table) {
            $table->dropColumn(['odontograma_state', 'odontograma_image']);
        });
    }
};
