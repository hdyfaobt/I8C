<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track WHICH person did the three key hand-offs in an order's
     * lifecycle, not just when they happened (the *_at timestamps already
     * cover the "when"):
     *   - created_by  — the receptionist who took/placed the order (store()/repeat()).
     *   - prepared_by — the orderpicker who assembled it and marked it ready (markReady()).
     *   - received_by — the receptionist who handed it over to the customer (markReceived()).
     *
     * nullOnDelete() rather than cascadeDelete(): if a user account is ever
     * removed, past orders should keep existing with this field simply
     * cleared, not get deleted along with the account.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('customer_id')
                ->constrained('users')->nullOnDelete();

            $table->foreignId('prepared_by')->nullable()->after('ready_at')
                ->constrained('users')->nullOnDelete();

            $table->foreignId('received_by')->nullable()->after('received_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropConstrainedForeignId('received_by');
        });
    }
};
