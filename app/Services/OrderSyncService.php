<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Syncs a 'pending' order to Salesforce and updates its status.
 *
 * This logic used to live only inside ConsumeOrders (the RabbitMQ
 * background worker), which meant nothing happened until someone manually
 * ran `php artisan rabbitmq:consume` in a terminal and kept it running.
 * That's not realistic for day-to-day use — everything needs to work from
 * the website alone.
 *
 * So this same logic is now shared between:
 *   - OrderController::accept()/retry(), which call it synchronously,
 *     right in the web request — the order is published to RabbitMQ (so
 *     the queue is still genuinely used) and then processed immediately,
 *     without waiting for a separate worker.
 *   - ConsumeOrders (the `rabbitmq:consume` command), which still works
 *     exactly as before if someone does choose to run it — the status
 *     check below makes it a safe no-op on an order that was already
 *     processed synchronously, so nothing gets synced twice.
 */
class OrderSyncService
{
    public function __construct(private SalesforceService $salesforce) {}

    /**
     * Sync a pending order to Salesforce and update its status accordingly.
     *
     * @return bool True if the order is (now, or already) synced.
     */
    public function process(Order $order): bool
    {
        // Only ever touch orders that are actually waiting to be synced.
        // If this runs twice for the same order (e.g. processed
        // synchronously already, then picked up again by the consumer),
        // the second call is a safe no-op.
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
