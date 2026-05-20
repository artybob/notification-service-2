<?php
return [
    'default' => 'redis',
    'stores' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        'array' => [
            'driver' => 'array',
        ],
    ],
];
