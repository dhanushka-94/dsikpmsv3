<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->date('recorded_on');
            $table->json('values');
            $table->decimal('result', 15, 4);
            $table->string('formula_snapshot')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['kpi_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_results');
    }
};
