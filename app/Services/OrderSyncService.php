<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

// Syncs pending orders to Salesforce
class OrderSyncService
{
    public function __construct(private SalesforceService $salesforce) {}

    // Sync one order, update status
    public function process(Order $order): bool
    {
        // Skip if already processed
        if ($order->status !== 'pending') {
            Log::info("[OrderSync] Order #{$order->id} is no longer 'pending' (status: {$order->status}) — skipping.");

            return $order->status === 'sent';
        }

        $opportunityId = $this->salesforce->syncOrder($order);

        if ($opportunityId) {
            $order->update(['status' => 'sent', 'sent_at' => now()]);
            Log::info("[OrderSync] Order #{$order->id} synced to Salesforce as Opportunity {$opportunityId}.");
        } else {
            $order->update(['status' => 'failed', 'failed_at' => now()]);
            Log::error("[OrderSync] Order #{$order->id} Salesforce sync failed.");
        }

        return (bool) $opportunityId;
    }
}
