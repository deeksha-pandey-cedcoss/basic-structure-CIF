<?php
return ['queue' =>
    [
        'amqp' => [
            'host' => '127.0.0.1',
            'port' => 5672,
            'username' => 'guest',
            'password' => 'guest',
            'virtual_host' => '/',
            'ssl' => [],
        ],
    ],
];
?>
