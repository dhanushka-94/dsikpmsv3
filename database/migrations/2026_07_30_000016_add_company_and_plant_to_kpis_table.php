<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('kpi_category_id')->constrained()->nullOnDelete();
            $table->foreignId('plant_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plant_id');
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
