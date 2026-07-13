<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move from "one order = one product" to "one order = many products".
     *
     * Each order can now have several line items (order_items), each with
     * its own product/quantity/unit_price and its own 'picked_at' — the
     * orderpicker confirms individual items as picked, not the whole order
     * at once.
     *
     * Existing orders keep their data: each one gets a single order_item
     * created from its current product/quantity/unit_price, then those
     * three columns are dropped from the orders table.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            // Set by the orderpicker when they've physically picked this
            // specific line item — see OrderController::pickItem().
            $table->timestamp('picked_at')->nullable();
            $table->timestamps();
        });

        // Backfill: turn every existing order's single product line into
        // its first (and only, for now) order_item.
        $now = now();

        DB::table('orders')->select('id', 'product', 'quantity', 'unit_price')->orderBy('id')->get()->each(function ($order) use ($now) {
            DB::table('order_items')->insert([
                'order_id' => $order->id,
                'product' => $order->product,
                'quantity' => $order->quantity,
                'unit_price' => $order->unit_price,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['product', 'quantity', 'unit_price']);
        });
    }

    /**
     * Reverse the migration.
     * Restores the columns and copies each order's first item back onto
     * the order row. If an order had more than one item, the extra items
     * are lost on rollback — this is a best-effort down(), not a perfect
     * mirror of up().
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('product')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10, 2)->nullable();
        });

        DB::table('orders')->select('id')->orderBy('id')->get()->each(function ($order) {
            $firstItem = DB::table('order_items')->where('order_id', $order->id)->orderBy('id')->first();

            if ($firstItem) {
                DB::table('orders')->where('id', $order->id)->update([
                    'product' => $firstItem->product,
                    'quantity' => $firstItem->quantity,
                    'unit_price' => $firstItem->unit_price,
                ]);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('product')->nullable(false)->change();
            $table->integer('quantity')->nullable(false)->change();
            $table->decimal('unit_price', 10, 2)->nullable(false)->change();
        });

        Schema::dropIfExists('order_items');
    }
};
