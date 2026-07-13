<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_results', function (Blueprint $table) {
            $table->dropForeign(['kpi_id']);
        });

        Schema::table('kpi_results', function (Blueprint $table) {
            $table->dropUnique(['kpi_id', 'recorded_on']);
        });

        Schema::table('kpi_results', function (Blueprint $table) {
            $table->foreign('kpi_id')->references('id')->on('kpis')->cascadeOnDelete();
            $table->index(['kpi_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::table('kpi_results', function (Blueprint $table) {
            $table->dropForeign(['kpi_id']);
            $table->dropIndex(['kpi_id', 'recorded_on']);
        });

        Schema::table('kpi_results', function (Blueprint $table) {
            $table->unique(['kpi_id', 'recorded_on']);
            $table->foreign('kpi_id')->references('id')->on('kpis')->cascadeOnDelete();
        });
    }
};
