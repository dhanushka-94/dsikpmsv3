<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->string('kpi_index')->nullable()->after('name');
        });

        foreach (DB::table('kpis')->get(['id', 'sort_order']) as $kpi) {
            DB::table('kpis')->where('id', $kpi->id)->update([
                'kpi_index' => (string) ($kpi->sort_order ?? ''),
            ]);
        }

        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('name');
        });

        foreach (DB::table('kpis')->get(['id', 'kpi_index']) as $kpi) {
            DB::table('kpis')->where('id', $kpi->id)->update([
                'sort_order' => is_numeric($kpi->kpi_index) ? (int) $kpi->kpi_index : 0,
            ]);
        }

        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('kpi_index');
        });
    }
};
