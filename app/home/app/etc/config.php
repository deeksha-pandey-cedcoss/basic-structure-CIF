<?php
return [
    'databases' => [
        'db' => [
            'adapter' => 'Mongo',
            'host' => 'mongodb://127.0.0.1:27017',
            'username' => 'root',
            'password' => 'cedcommerce',
            'dbname' => 'home',
        ],
        'db_mongo' => [
            'adapter' => 'Mongo',
            'host' => 'mongodb://127.0.0.1:27017',
            'username' => 'root',
            'password' => 'cedcommerce',
            'dbname' => 'home',
        ],
    ],
    'mailer' => [
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => '587',
            'username' => 'your_email',
            'password' => 'your_password',
        ],
        'sender_email' => 'sender_email',
        'sender_name' => 'email_sender_name'
    ],
    'di' => [
        'mailer' => '\App\Core\Components\Mailers\PhpMailer',
        'log' => 'App\Core\Components\Log'
    ],

    'otp_details' => [
        'sender_phone' => 'your_phone',
        'account_number' => 'your_account_number',
        'access_token' => 'your_access_token'
    ],
    'webhook_api_base' => [
        'shopify' => 'shopify/hook/'
    ],
    'webhook_api_base_fba' => [
        'fba' => 'fba/hook/',
        'ali' => 'aliexpress/hook/'
    ],
    'webhooks' => [
        'shopify' => [
            'orders/create' => 'createOrder',
            'orders/updated' => 'updateOrder',
        ]
    ],
    'uninstall_class' => '\App\Amazonimporter\Components\Helper',
    'app_code' => 'onyx',
    'frontend_app_url' => 'your_frontend_app_url',
    'redirect_after_install' => '/show/message',
    'server_ip' => 'server_ip',
    'current_db' => 'db',
    'default_db' => 'db',
    'backend_base_url' => 'http://remote.local.cedcommerce.com:8080/',
    'apiconnector' => [
        'base_url' => 'http://remote.local.cedcommerce.com:8080/',

        'default' => [
            'refresh_token' => 'your_refresh_token'
        ],
        'your_app_name' => [
           'default'=>[
            'sub_app_id' => 'y0ur_aap_id(int)',
            'refresh_token' => 'your_refresh_token',
            'public_key' =>
                'your_public_key'
           ]
        ]
    ],
    'rabbitmq_url' => 'rabbitmq-url',
    'mail_through_rabbitmq' => false,
    'enable_rabbitmq' => true,
    'app_name' => 'Omni Importer',
    'enable_rabbitmq_internal' => true,
    'app_token' => 'app-token',
    'slave_enabled' => false,
    'under_maintenance' => false,
    'services' => [
        'product_import' => [
            'handler' => '\App\Connector\Components\ImportServiceHelper',
            'code' => 'product_import',
            'title' => 'Product Import',
            'type' => 'importer',
            'charge_type' => 'prepaid',
            'marketplace' => 'global',
            'image' => 'marketplace-logos/amazon.jpg'
        ],
        'product_sync' => [
            'handler' => '\App\Connector\Components\SyncHelper',
            'code' => 'product_sync',
            'title' => 'Product Sync',
            'type' => 'importer',
            'charge_type' => 'prepaid',
            'marketplace' => 'global',
            'image' => 'marketplace-logos/amazon.jpg'
        ]
    ]
];


