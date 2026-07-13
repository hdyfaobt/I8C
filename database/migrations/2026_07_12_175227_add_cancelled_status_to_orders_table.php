<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the orders.status enum with a 'cancelled' value.
     * Lets a user cancel an order while it is still 'pending'
     * (i.e. before the RabbitMQ consumer has synced it to Salesforce).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migration.
     * Note: any orders already marked 'cancelled' would violate the
     * original enum — this app has no need to roll back in production,
     * so we keep the down() simple.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'sent', 'failed'])
                ->default('pending')
                ->change();
        });
    }
};
