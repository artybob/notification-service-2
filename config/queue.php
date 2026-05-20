<?php

return [
    'default' => env('QUEUE_CONNECTION', 'rabbitmq'),
    
    'connections' => [
        'rabbitmq' => [
            'driver' => 'rabbitmq',
            'queue' => env('RABBITMQ_QUEUE', 'notifications'),
            'hosts' => [
                [
                    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
                    'port' => env('RABBITMQ_PORT', 5672),
                    'user' => env('RABBITMQ_LOGIN', 'guest'),
                    'password' => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost' => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'exchange' => [
                    'name' => 'notifications_exchange',
                    'type' => 'direct',
                    'durable' => true,
                ],
                'queue' => [
                    'arguments' => [
                        'x-max-priority' => 10,
                    ],
                ],
            ],
        ],
    ],
];
