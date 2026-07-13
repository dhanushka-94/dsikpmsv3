<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        $departmentId = 1;
        foreach (DB::table('departments')->orderBy('name')->pluck('id') as $id) {
            DB::table('departments')->where('id', $id)->update(['sort_order' => $departmentId++]);
        }

        $designationId = 1;
        foreach (DB::table('designations')->orderBy('name')->pluck('id') as $id) {
            DB::table('designations')->where('id', $id)->update(['sort_order' => $designationId++]);
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });

        Schema::table('designations', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
