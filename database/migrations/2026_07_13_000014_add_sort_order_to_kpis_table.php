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
            $table->unsignedInteger('sort_order')->default(0)->after('name');
        });

        $order = 1;
        foreach (DB::table('kpis')->orderBy('id')->pluck('id') as $id) {
            DB::table('kpis')->where('id', $id)->update(['sort_order' => $order++]);
        }
    }

    public function down(): void
    {
        Schema::table('kpis', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
