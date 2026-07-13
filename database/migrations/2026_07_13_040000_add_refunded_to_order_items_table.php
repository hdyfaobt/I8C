<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When an item is marked out of stock (see out_of_stock_at), the
     * customer is owed a refund for that line — see
     * OrderController::markItemOutOfStock(), which auto-adds a note about
     * it. This column tracks whether the receptionist/manager/admin has
     * actually processed that refund yet, so it can show up as an open task
     * on the refunds page (see RefundController) until it's dealt with.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('refunded_at')->nullable()->after('out_of_stock_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('refunded_at');
        });
    }
};
