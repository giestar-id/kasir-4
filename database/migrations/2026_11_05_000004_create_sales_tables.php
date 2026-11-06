<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('channel')->default('offline'); // offline, gofood, shopeefood
            $table->string('external_order_id')->nullable();
            $table->string('status')->default('completed'); // completed, void
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->string('discount_type')->nullable(); // fixed, percent
            $table->text('discount_reason')->nullable();
            $table->bigInteger('platform_adjustment')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->string('payment_method')->default('cash'); // cash, qris, transfer
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('change_amount')->default(0);
            $table->dateTime('ordered_at');
            $table->string('source')->default('manual'); // manual, ai_api, ai_local
            $table->string('idempotency_key')->unique();
            $table->dateTime('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['channel', 'external_order_id']);
            $table->index('ordered_at');
            $table->index(['channel', 'ordered_at']);
            $table->index(['status', 'ordered_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete();
            $table->string('menu_name_snapshot');
            $table->integer('qty');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('unit_hpp')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('sale_id');
            $table->index('menu_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
