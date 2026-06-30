<?php

namespace App\Services;

use App\Models\Order;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class RabbitMQPublisher
{
    /**
     * Publish an order message to the RabbitMQ queue.
     * Called from OrderController after a new order is created.
     *
     * @param Order $order The order to publish
     * @return bool True on success, false on failure
     */
    public function publishOrder(Order $order): bool
    {
        try {
            // Open the connection to RabbitMQ
            $connection = $this->connect();
            $channel    = $connection->channel();

            // Declare the queue (creates it if it doesn't exist yet)
            // durable: true = queue survives a RabbitMQ restart
            $channel->queue_declare(
                queue:   config('rabbitmq.queue'),
                passive: false,
                durable: true,
                exclusive: false,
                auto_delete: false
            );

            // Build the message payload as JSON
            $payload = json_encode([
                'order_id'    => $order->id,
                'customer_id' => $order->customer_id,
                'customer'    => $order->customer->name,
                'product'     => $order->product,
                'quantity'    => $order->quantity,
                'unit_price'  => $order->unit_price,
                'total'       => $order->totalPrice(),
                'notes'       => $order->notes,
                'created_at'  => $order->created_at->toISOString(),
            ]);

            // Create the AMQP message
            // delivery_mode: 2 = persistent (survives broker restart)
            $message = new AMQPMessage($payload, [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type'  => 'application/json',
            ]);

            // Publish to the default exchange, routed to our queue
            $channel->basic_publish(
                msg:         $message,
                exchange:    '',
                routing_key: config('rabbitmq.queue')
            );

            Log::info("[RabbitMQ] Order #{$order->id} published to queue.");

            // Clean up the connection
            $channel->close();
            $connection->close();

            return true;

        } catch (\Exception $e) {
            Log::error("[RabbitMQ] Failed to publish order #{$order->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Open a connection to RabbitMQ.
     * Uses SSL (AMQPSSLConnection) for CloudAMQP, plain for local.
     */
    private function connect(): AMQPSSLConnection|AMQPStreamConnection
    {
        $host     = config('rabbitmq.host');
        $port     = config('rabbitmq.port');
        $user     = config('rabbitmq.user');
        $password = config('rabbitmq.password');
        $vhost    = config('rabbitmq.vhost');

        if (config('rabbitmq.ssl')) {
            // SSL connection for CloudAMQP (amqps://)
            return new AMQPSSLConnection($host, $port, $user, $password, $vhost, [
                'verify_peer' => true,
            ]);
        }

        // Plain connection for local RabbitMQ
        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }
}
