<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (! Schema::hasColumn('orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable();
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });

        if (! $this->indexExists('payments', 'payments_provider_payment_id_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('provider_payment_id');
            });
        }

        if (! $this->indexExists('play_wallet_orders', 'play_wallet_orders_order_id_unique')) {
            Schema::table('play_wallet_orders', function (Blueprint $table) {
                $table->unique('order_id');
            });
        }
    }

    public function down(): void
    {
        // Corrective production migration: intentionally irreversible.
    }

    private function indexExists(string $table, string $index): bool
    {
        try {
            return Schema::hasIndex($table, $index);
        } catch (\Throwable) {
            return false;
        }
    }
};
