<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('departments', 'parent_id')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('departments', 'parent_id')) {
            return;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
