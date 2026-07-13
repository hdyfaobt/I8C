<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add timestamp columns tracking exactly when an order's status last
     * changed to 'sent', 'failed' or 'cancelled'.
     *
     * Note: 'created_at' already acts as the order date ("besteldatum") and
     * 'updated_at' is not reliable for this because it gets overwritten on
     * every update (retry, repeat, ...), not just on a status change.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('salesforce_id');
            $table->timestamp('failed_at')->nullable()->after('sent_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'failed_at', 'cancelled_at']);
        });
    }
};
