<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a "paid" flag to orders.
     *
     * This is deliberately kept OUT of the main `status` lifecycle column
     * (awaiting_review -> pending -> sent -> ready_for_pickup -> received,
     * or refused/cancelled/failed). Whether an order has been paid is a
     * separate, independent fact that can become true at any point in that
     * lifecycle — it doesn't block or gate any of the existing workflow
     * steps. It's shown as its own column in the UI and toggled by
     * receptionist/manager/admin only.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('paid')->default(false)->after('status');
            $table->timestamp('paid_at')->nullable()->after('paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid', 'paid_at']);
        });
    }
};
