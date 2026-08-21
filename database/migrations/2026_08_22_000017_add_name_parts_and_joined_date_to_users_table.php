<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('calling_name')->nullable()->after('title');
            $table->string('middle_initials')->nullable()->after('calling_name');
            $table->string('last_name')->nullable()->after('middle_initials');
            $table->date('joined_date')->nullable()->after('epf_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['calling_name', 'middle_initials', 'last_name', 'joined_date']);
        });
    }
};
