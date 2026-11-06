<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name')->unique();
            $table->unsignedBigInteger('price');
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
        });

        Schema::create('menu_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('normalized_alias')->unique();
            $table->timestamps();

            $table->index('menu_id');
        });

        Schema::create('menu_extra_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->index('menu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_extra_costs');
        Schema::dropIfExists('menu_aliases');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('categories');
    }
};
