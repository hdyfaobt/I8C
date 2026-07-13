<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track HOW an order was paid, alongside the existing `paid`/`paid_at`
     * flag (see add_paid_status_to_orders_table). Set to either
     * 'bank_transfer' (covers both a regular bank transfer and Bancontact —
     * both are non-cash, traceable payments) or 'cash', chosen by the
     * receptionist/admin/manager at the moment they mark an order as paid.
     * Cleared again if the payment is later undone (see
     * OrderController::togglePaid()), since it's no longer accurate once
     * the order is back to "unpaid".
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
