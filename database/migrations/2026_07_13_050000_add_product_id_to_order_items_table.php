<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link an order item back to the catalog product it was created from.
     *
     * order_items.product only ever stored the product NAME as a plain
     * string snapshot (see the create_order_items_table migration) — that
     * was enough to display a line item, but it silently breaks
     * ProductController::history() as soon as a product is renamed: old
     * order_items still have the OLD name, so they no longer match
     * Product::name and disappear from that product's history. The
     * catalog only having one product per name made this easy to miss
     * until a product was actually renamed.
     *
     * product_id fixes that going forward, without throwing away the old
     * matching: history() still falls back to matching by name for any
     * order_item created before this migration (product_id null).
     *
     * nullOnDelete(): deleting a product from the catalog should not wipe
     * out historical order lines that reference it — see
     * ProductController::destroy().
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('order_id')
                ->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
