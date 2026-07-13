<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->json('formula_values')->nullable()->after('formula');
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('formula_values');
        });
    }
};
