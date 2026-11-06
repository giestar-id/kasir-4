<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->index('sort_order');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['is_active', 'name']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->index('ingredient_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('purchased_at');
            $table->index('user_id');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['type', 'created_at']);
            $table->index('created_at');
            $table->index('user_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['channel', 'status', 'ordered_at']);
            $table->index('user_id');
        });

        Schema::table('screenshot_imports', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('sale_id');
            $table->index('uploaded_by');
            $table->index('platform');
        });
    }

    public function down(): void
    {
        Schema::table('screenshot_imports', function (Blueprint $table) {
            $table->dropIndex(['platform']);
            $table->dropIndex(['uploaded_by']);
            $table->dropIndex(['sale_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['channel', 'status', 'ordered_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['type', 'created_at']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['purchased_at']);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['ingredient_id']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'name']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
        });
    }
};
