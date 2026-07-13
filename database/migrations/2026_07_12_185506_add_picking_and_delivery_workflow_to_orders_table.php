<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the order lifecycle past 'sent' with two more human steps:
     *
     *   sent -> (orderpicker picks every item, optionally leaves a comment,
     *            then confirms) -> 'ready_for_pickup' -> (receptionist
     *            confirms the customer actually took the order) -> 'received'
     *
     * 'received' is the final, successful end state of an order.
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
                'ready_for_pickup',
                'received',
            ])->default('awaiting_review')->change();

            // Free-text note left by the orderpicker while preparing the
            // order (e.g. "product X was out of stock, replaced with Y").
            $table->text('picking_comment')->nullable()->after('refused_at');

            $table->timestamp('ready_at')->nullable()->after('picking_comment');
            $table->timestamp('received_at')->nullable()->after('ready_at');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['picking_comment', 'ready_at', 'received_at']);

            $table->enum('status', [
                'awaiting_review',
                'pending',
                'sent',
                'failed',
                'refused',
                'cancelled',
            ])->default('awaiting_review')->change();
        });
    }
};
