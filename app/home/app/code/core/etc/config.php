<?php
return [
        'di'=>[
                'App\Core\Components\Container\CacheInterface' => 'App\Core\Components\Cache',
                'session'=>'App\Core\Components\Session',
                'log'=>'App\Core\Components\Log',
                'messageManager'=>'App\Core\Components\Message\Handler\Sqs',
                'appCode' => 'App\Core\Components\AppCode',
                'translation' => 'App\Core\Components\Translation'
            ],
            'base_path_removal' => 'aliexpress/public/',
        'routers'=>[
                'App\Core\Components\Router'
            ],
        'events'=>[
            'application:beforeHandleRequest' => [
                'firewall'=>'\App\Core\Middlewares\Firewall'
            ],
        ],
        'open_urls'=> [
            'module_controller_action'=>1
        ]
    ];
