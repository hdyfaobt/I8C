<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Connection Settings
    |--------------------------------------------------------------------------
    | All values come from .env — never hardcode credentials here.
    */

    'host'     => env('RABBITMQ_HOST', 'localhost'),
    'port'     => (int) env('RABBITMQ_PORT', 5672),
    'user'     => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost'    => env('RABBITMQ_VHOST', '/'),
    'queue'    => env('RABBITMQ_QUEUE', 'orders'),

    // Use SSL (true for CloudAMQP amqps://, false for local amqp://)
    'ssl'      => env('RABBITMQ_SSL', true),
];
