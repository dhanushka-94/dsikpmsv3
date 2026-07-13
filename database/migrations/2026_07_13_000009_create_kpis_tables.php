<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('kpi_category_id')->constrained()->restrictOnDelete();
            $table->text('definition')->nullable();
            $table->string('formula');
            $table->decimal('formula_result', 15, 4)->nullable();
            $table->decimal('benchmark_percent', 8, 2);
            $table->string('benchmark_type'); // increase | decrease
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kpi_project', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['kpi_id', 'project_id']);
        });

        Schema::create('kpi_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('weightage', 8, 2);
            $table->timestamps();

            $table->unique(['kpi_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_user');
        Schema::dropIfExists('kpi_project');
        Schema::dropIfExists('kpis');
        Schema::dropIfExists('kpi_categories');
    }
};
