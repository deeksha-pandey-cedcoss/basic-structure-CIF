<?php
return [
    'databases' => [
        'db' => [
            'adapter' => 'Mongo',
            'host' => 'mongodb://127.0.0.1:27017',
            'username' => 'root',
            'password' => 'cedcommerce',
            'dbname' => 'remote',
		    ],
		    'db_mongo'=>[
                'adapter'  => 'Mongo',
                'host'     => 'mongodb://127.0.0.1:27017',
                'username' => 'root',
                'password' => 'cedcommerce',
                'dbname'   => 'remote'
            ],

    ],

    'mailer'=>[
        'smtp' => [
            'host' => 'mailserver.cedcommerce.com',
            'port' => '587',
            'username' => 'youe_email',
            'password' => 'your_password',

        ],
        'sender_email' => 'sender_email',
        'sender_name' => 'email_sender_name'
    ],
    'di' => [
        'mailer' => '\App\Core\Components\Mailers\PhpMailer',
        'log' => 'App\Core\Components\Log',
    ],
    'app_code' => 'apiconnector',
    'frontend_app_url' => 'your_frontend_url',
    'redirect_after_install' => '/panel/apps/availableapps',
    'frontend_force_app_url' => 'http://admin.local.cedcommerce.com:3000/show/requeststatus',
    'server_ip' => '127.0.0.1',
    'current_db' => 'db',
    'default_db' => 'db',
    'backend_base_url' => 'http://remote.local.cedcommerce.com/',
    'rabbitmq_url' => 'rabbitmq-url',
    'mail_through_rabbitmq' => false,
    'enable_rabbitmq' => false,
    'enable_rabbitmq_internal'=>false,
    'app_token' => 'app-token',
    'request_log_enabled' => true,
    'fb_app_system_token' => 'your_fb_app_system',
    'amazon' => [
        'sub_app_id' => 8,
       
        'north_america' => [
            'access_key' => 'your_access_key',
            'secret_key' => 'your_secret_key',
            'authenticate_url' => 'your_authenticate_url'
        ],
       
        'home_auth_url' => 'http://127.0.0.1/home/public/connector/request/commenceHomeAuth?sAppId=8',
        'refresh_token' => 'your_refresh_token',
        'public_key' =>
            'your_public_key'

    ],
];
