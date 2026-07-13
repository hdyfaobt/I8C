<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeOrders extends Command
{
    /**
     * The artisan command signature.
     * Run with: php artisan rabbitmq:consume
     *
     * NOTE: this is now OPTIONAL. Orders are processed synchronously as
     * soon as a receptionist/orderpicker clicks "Accepteren" or "Opnieuw
     * proberen" on the website (see OrderController + OrderSyncService) —
     * nobody needs to keep this command running for the app to work.
     * It's kept around as a safety net (e.g. an order published but never
     * synced because the web request got interrupted) and as a visible
     * example of the RabbitMQ consumer pattern.
     */
    protected $signature = 'rabbitmq:consume';

    /**
     * Description shown in php artisan list.
     */
    protected $description = 'Listen to the RabbitMQ orders queue and process incoming messages (optional — see class docblock)';

    /**
     * Start the consumer loop — runs indefinitely until stopped (Ctrl+C).
     */
    public function handle(): void
    {
        $this->info('[RabbitMQ Consumer] Starting... Press Ctrl+C to stop.');

        try {
            $connection = $this->connect();
            $channel = $connection->channel();

            // Declare the same queue as the publisher (idempotent)
            $channel->queue_declare(
                queue: config('rabbitmq.queue'),
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false
            );

            // Only fetch 1 message at a time — fair dispatch
            $channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);

            $this->info('[RabbitMQ Consumer] Waiting for messages on queue: '.config('rabbitmq.queue'));

            // NOTE: we deliberately do NOT auto-retry here. An immediate
            // requeue would hit Salesforce again within milliseconds, with
            // the exact same data — if it failed once, it will fail the
            // same way every time right away (bad payload, closed
            // opportunity, expired token, ...). Retrying blindly like that
            // is pointless. Instead, a single failed attempt leaves the
            // order with status "failed" so a human can inspect it and
            // trigger a manual retry later (see OrderController::retry()),
            // once whatever caused the failure has actually been fixed.
            $callback = function (AMQPMessage $message) {
                $data = json_decode($message->body, true);

                $this->info("[RabbitMQ Consumer] Received order #{$data['order_id']} for {$data['customer']}");

                $success = $this->processOrder($data);

                // Always acknowledge: the order's status ('sent' or 'failed')
                // is now stored in the database, so the message itself no
                // longer needs to stay in the queue. Manual retries publish
                // a brand new message instead of relying on requeueing.
                $message->ack();

                if ($success) {
                    $this->info("[RabbitMQ Consumer] Order #{$data['order_id']} synced successfully.");
                } else {
                    $this->error("[RabbitMQ Consumer] Order #{$data['order_id']} failed — left as 'failed' for manual retry.");
                }
            };

            $channel->basic_consume(
                queue: config('rabbitmq.queue'),
                consumer_tag: '',
                no_local: false,
                no_ack: false,
                exclusive: false,
                nowait: false,
                callback: $callback
            );

            // Keep listening until the channel is closed
            while ($channel->is_consuming()) {
                $channel->wait();
            }

            $channel->close();
            $connection->close();

        } catch (\Exception $e) {
            $this->error('[RabbitMQ Consumer] Connection error: '.$e->getMessage());
            Log::error('[RabbitMQ Consumer] '.$e->getMessage());
        }
    }

    /**
     * Process a received order message.
     * Delegates the actual sync + status update to OrderSyncService, the
     * same service OrderController uses for the synchronous accept/retry
     * flow — so there is only one place that knows how to sync an order to
     * Salesforce and update its status, regardless of whether it's called
     * from the website directly or from this background worker.
     *
     * @param  array  $data  The decoded message payload
     * @return bool True on success, false on failure
     */
    private function processOrder(array $data): bool
    {
        try {
            $order = Order::with(['customer', 'items'])->find($data['order_id']);

            if (! $order) {
                Log::warning("[RabbitMQ Consumer] Order #{$data['order_id']} not found in database.");

                return false;
            }

            // OrderSyncService itself skips anything that isn't 'pending'
            // (e.g. already processed synchronously by the website, or
            // cancelled while the message was sitting in the queue) —
            // so this is safe to call even on an order that was already
            // handled elsewhere.
            return app(OrderSyncService::class)->process($order);

        } catch (\Exception $e) {
            Log::error("[RabbitMQ Consumer] Failed to process order #{$data['order_id']}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Open a RabbitMQ connection (SSL or plain).
     */
    private function connect(): AMQPSSLConnection|AMQPStreamConnection
    {
        $host = config('rabbitmq.host');
        $port = config('rabbitmq.port');
        $user = config('rabbitmq.user');
        $password = config('rabbitmq.password');
        $vhost = config('rabbitmq.vhost');

        if (config('rabbitmq.ssl')) {
            return new AMQPSSLConnection($host, $port, $user, $password, $vhost, [
                'verify_peer' => true,
            ]);
        }

        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }
}
