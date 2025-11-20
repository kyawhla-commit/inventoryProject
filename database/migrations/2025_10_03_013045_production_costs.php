<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained()->onDelete('cascade');
            
            // Material Costs
            $table->decimal('planned_material_cost', 12, 2)->default(0);
            $table->decimal('actual_material_cost', 12, 2)->default(0);
            $table->decimal('material_variance', 12, 2)->default(0);
            
            // Labor Costs
            $table->decimal('planned_labor_cost', 12, 2)->default(0);
            $table->decimal('actual_labor_cost', 12, 2)->default(0);
            $table->decimal('labor_variance', 12, 2)->default(0);
            
            // Overhead Costs
            $table->decimal('planned_overhead_cost', 12, 2)->default(0);
            $table->decimal('actual_overhead_cost', 12, 2)->default(0);
            $table->decimal('overhead_variance', 12, 2)->default(0);
            
            // Total Costs
            $table->decimal('total_planned_cost', 12, 2)->default(0);
            $table->decimal('total_actual_cost', 12, 2)->default(0);
            $table->decimal('total_variance', 12, 2)->default(0);
            
            // GL Integration
            $table->string('material_gl_code')->nullable();
            $table->string('labor_gl_code')->nullable();
            $table->string('overhead_gl_code')->nullable();
            
            $table->timestamps();
            $table->index(['production_plan_id', 'created_at']);
        });

        Schema::create('cost_variance_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_cost_id')->constrained()->onDelete('cascade');
            $table->enum('cost_type', ['material', 'labor', 'overhead']);
            $table->decimal('variance_amount', 12, 2);
            $table->string('reason');
            $table->text('description')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('labor_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained()->onDelete('cascade');
            $table->string('labor_type');
            $table->integer('planned_hours');
            $table->integer('actual_hours')->default(0);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('planned_cost', 12, 2);
            $table->decimal('actual_cost', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('overhead_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_plan_id')->constrained()->onDelete('cascade');
            $table->string('overhead_type');
            $table->decimal('planned_amount', 12, 2);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->string('allocation_basis');
            $table->decimal('allocation_rate', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overhead_costs');
        Schema::dropIfExists('labor_costs');
        Schema::dropIfExists('cost_variance_reasons');
        Schema::dropIfExists('production_costs');
    }
};