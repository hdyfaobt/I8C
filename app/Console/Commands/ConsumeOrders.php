<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderSyncService;
use App\Services\RabbitMQPublisher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeOrders extends Command
{
    // Optional safety-net worker
    protected $signature = 'rabbitmq:consume';

    // Shown in artisan list
    protected $description = 'Listen to the RabbitMQ orders queue and process incoming messages (optional)';

    // Start the consumer loop
    public function handle(): void
    {
        $this->info('[RabbitMQ Consumer] Starting... Press Ctrl+C to stop.');

        try {
            $connection = app(RabbitMQPublisher::class)->connect();
            $channel = $connection->channel();

            // Declare queue (idempotent)
            $channel->queue_declare(
                queue: config('rabbitmq.queue'),
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false
            );

            // One message at a time
            $channel->basic_qos(prefetch_size: 0, prefetch_count: 1, a_global: false);

            $this->info('[RabbitMQ Consumer] Waiting for messages on queue: '.config('rabbitmq.queue'));

            // No auto-retry on failure
            $callback = function (AMQPMessage $message) {
                $data = json_decode($message->body, true);

                $this->info("[RabbitMQ Consumer] Received order #{$data['order_id']} for {$data['customer']}");

                $success = $this->processOrder($data);

                // Always acknowledge the message
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

            // Listen until channel closes
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

    // Process one order message
    private function processOrder(array $data): bool
    {
        try {
            $order = Order::with(['customer', 'items'])->find($data['order_id']);

            if (! $order) {
                Log::warning("[RabbitMQ Consumer] Order #{$data['order_id']} not found in database.");

                return false;
            }

            // Skips already-handled orders
            return app(OrderSyncService::class)->process($order);

        } catch (\Exception $e) {
            Log::error("[RabbitMQ Consumer] Failed to process order #{$data['order_id']}: ".$e->getMessage());

            return false;
        }
    }
}
