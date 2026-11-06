<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_unit'); // gram, ml, pcs
            $table->string('purchase_unit'); // kg, liter, pack, etc.
            $table->decimal('unit_factor', 14, 3)->default(1);
            $table->unsignedBigInteger('avg_cost_per_base')->default(0);
            $table->decimal('current_stock', 14, 3)->default(0);
            $table->decimal('min_stock', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'current_stock']);
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_per_portion', 14, 3);
            $table->timestamps();

            $table->unique(['menu_id', 'ingredient_id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_purchase_unit', 14, 3);
            $table->decimal('qty_base', 14, 3);
            $table->unsignedBigInteger('total_cost');
            $table->unsignedBigInteger('unit_cost_per_base')->default(0);
            $table->dateTime('purchased_at');
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['ingredient_id', 'purchased_at']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // purchase, sale_usage, void_return, adjustment, waste
            $table->decimal('qty_change', 14, 3); // positive or negative
            $table->unsignedBigInteger('unit_cost')->default(0);
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('reason')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['ingredient_id', 'created_at']);
            $table->index(['ref_type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('ingredients');
    }
};
