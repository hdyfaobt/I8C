<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A third possible outcome for an order item, alongside "picked" —
     * sometimes the orderpicker finds the product isn't actually available
     * anymore. Marking it out of stock (rather than forcing a "picked" that
     * isn't true) is what lets the order still be completed and moved to
     * "ready for pickup" — see Order::allItemsResolved() and
     * OrderController::markItemOutOfStock(), which also auto-adds a note
     * about refunding the customer for that item.
     *
     * Mutually exclusive with picked_at in practice (an item is either
     * picked or out of stock, never both) — enforced in the controller,
     * not at the database level.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('out_of_stock_at')->nullable()->after('picked_at');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('out_of_stock_at');
        });
    }
};
