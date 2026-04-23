<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('steam_login');
            $table->string('region')->default('ru');
            $table->unsignedInteger('amount_rub');
            $table->unsignedInteger('total_rub');
            $table->string('promo_code')->nullable();
            $table->string('payment_method');
            $table->string('status')->default('pending');
            $table->string('playwallet_order_id')->nullable();
            $table->string('playwallet_status')->nullable();
            $table->json('playwallet_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

