<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the manual review step to the order status enum, and the two
     * timestamp columns that go with it.
     *
     * New flow: an order is created as 'awaiting_review' (NOT published to
     * RabbitMQ yet). A receptionist then either:
     *   - accepts it -> status becomes 'pending' and it is published to
     *     RabbitMQ, exactly like the old "create order" flow.
     *   - refuses it -> status becomes 'refused', it never reaches
     *     Salesforce.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', [
                'awaiting_review',
                'pending',
                'sent',
                'failed',
                'refused',
                'cancelled',
            ])->default('awaiting_review')->change();

            $table->timestamp('accepted_at')->nullable()->after('cancelled_at');
            $table->timestamp('refused_at')->nullable()->after('accepted_at');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['accepted_at', 'refused_at']);

            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }
};
