<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\SalesforceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ConsumeOrders extends Command
{
    /**
     * The artisan command signature.
     * Run with: php artisan rabbitmq:consume
     */
    protected $signature = 'rabbitmq:consume';

    /**
     * Description shown in php artisan list.
     */
    protected $description = 'Listen to the RabbitMQ orders queue and process incoming messages';

    /**
     * Give up on a message after this many failed attempts, instead of
     * requeuing it forever (e.g. an order that will never sync, a
     * permanently broken Salesforce record, ...).
     */
    private const MAX_ATTEMPTS = 3;

    /**
     * AMQP header key used to track how many times a message was retried.
     */
    private const RETRY_HEADER = 'x-retry-count';

    /**
     * Start the consumer loop — runs indefinitely until stopped (Ctrl+C).
     */
    public function handle(): void
    {
        $this->info('[RabbitMQ Consumer] Starting... Press Ctrl+C to stop.');

        try {
            // Open the connection
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

            // Define the callback executed for each incoming message
            $callback = function (AMQPMessage $message) use ($channel) {
                $data = json_decode($message->body, true);
                $attempt = $this->retryCount($message);

                $this->info("[RabbitMQ Consumer] Received order #{$data['order_id']} for {$data['customer']} (attempt ".($attempt + 1).')');

                // Process the order — update status in database
                $success = $this->processOrder($data);

                if ($success) {
                    // Acknowledge: tell RabbitMQ the message was handled
                    $message->ack();
                    $this->info("[RabbitMQ Consumer] Order #{$data['order_id']} acknowledged.");

                    return;
                }

                if ($attempt + 1 >= self::MAX_ATTEMPTS) {
                    // Give up — remove the message from the queue instead of
                    // requeuing it forever. The order is left with status
                    // "failed" (set in processOrder()) for manual follow-up.
                    $message->ack();
                    $this->error("[RabbitMQ Consumer] Order #{$data['order_id']} failed after ".self::MAX_ATTEMPTS.' attempts — giving up.');
                    Log::error("[RabbitMQ Consumer] Order #{$data['order_id']} abandoned after ".self::MAX_ATTEMPTS.' attempts.');

                    return;
                }

                // Remove the original message and republish it with an
                // incremented retry counter — plain nack(requeue: true)
                // would redeliver it forever with no way to count attempts.
                $message->ack();
                $this->requeueWithBackoff($channel, $message, $attempt + 1);
                $this->error("[RabbitMQ Consumer] Order #{$data['order_id']} failed — requeued (attempt ".($attempt + 1).'/'.self::MAX_ATTEMPTS.').');
            };

            // Register the consumer
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
     * Syncs the order to Salesforce via the REST API.
     *
     * @param  array  $data  The decoded message payload
     * @return bool True on success, false on failure
     */
    private function processOrder(array $data): bool
    {
        try {
            $order = Order::with('customer')->find($data['order_id']);

            if (! $order) {
                Log::warning("[RabbitMQ Consumer] Order #{$data['order_id']} not found in database.");

                return false;
            }

            // Sync to Salesforce — creates Account + Opportunity
            $opportunityId = app(SalesforceService::class)->syncOrder($order);

            if ($opportunityId) {
                $order->update(['status' => 'sent']);
                Log::info("[RabbitMQ Consumer] Order #{$order->id} synced to Salesforce as Opportunity {$opportunityId}.");
            } else {
                $order->update(['status' => 'failed']);
                Log::error("[RabbitMQ Consumer] Order #{$order->id} Salesforce sync failed.");
            }

            return (bool) $opportunityId;

        } catch (\Exception $e) {
            Log::error("[RabbitMQ Consumer] Failed to process order #{$data['order_id']}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Read the current retry count from a message's AMQP headers.
     * Messages seen for the first time simply have no header (attempt 0).
     */
    private function retryCount(AMQPMessage $message): int
    {
        if (! $message->has('application_headers')) {
            return 0;
        }

        /** @var AMQPTable $headers */
        $headers = $message->get('application_headers');

        return (int) ($headers->getNativeData()[self::RETRY_HEADER] ?? 0);
    }

    /**
     * Republish a failed message onto the same queue with its retry
     * counter incremented, so the next delivery knows how many attempts
     * have already been made.
     */
    private function requeueWithBackoff(AMQPChannel $channel, AMQPMessage $original, int $nextAttempt): void
    {
        $retryMessage = new AMQPMessage($original->getBody(), [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => 'application/json',
            'application_headers' => new AMQPTable([self::RETRY_HEADER => $nextAttempt]),
        ]);

        $channel->basic_publish($retryMessage, exchange: '', routing_key: config('rabbitmq.queue'));
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
