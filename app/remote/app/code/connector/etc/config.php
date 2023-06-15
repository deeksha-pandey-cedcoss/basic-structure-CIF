<?php
return [
    'di'=>[
            '\MyInterface'=>'\App\Core\Components\Router',
            'suggestionHelper' => '\App\Connector\Components\Suggestor',
            'importHelper' => '\App\Connector\Components\ImportHelper'
    ],
    'routers'=>[
            'App\Core\Components\Router'
        ],
    'events'=>[
        // 'application:createAfter' => [
        //     'user_create' => '\App\Connector\Components\UserCreateEvent'
        // ],
         'application:productSaveBefore' => [
             'product_save_before' => '\App\Connector\Components\ProductEvent'
         ],
         'application:productSaveAfter' => [
             'product_save_after' => '\App\Connector\Components\ProductEvent'
         ],
        // 'profile:beforeSave' => [
        //     'ebay_profile_save_before' => '\App\Connector\Components\Testprofile'
        // ],
        // 'account:afterDisconnect'=>[
        //     'onyx_account_disconnect_after'=> '\App\Onyx\Components\Testprofile'
        // ]
    ],
     'connectors'=>[
            'global'=>[
                'type' => 'proxy',
                'code'=>'global',
                'is_source' => 1,
                'image' => 'marketplace-logos/shopify.png',
                'title' => 'Global',
                'description' => 'Shopify integration',
                'source_model' => '\App\Core\Models\SourceModel'
            ]
        ],
    'warehouse_handle'=>[
        'default'=>[
            'source'=>'\App\Connector\Components\DefaultHandler',
            'code' => 'global'
        ]
    ],
    'payment_methods' => [
        'shopify' => [
            'shopify_payment' => [
                            'title' => 'Shopify Payment',
                            'source_model' => '\App\Shopify\Components\PaymentHelper',
                            'type' => 'redirect',
                            'code' => 'shopify_payment',
                            'description' => 'Official payment method of shopify'
                        ]
        ]
    ],
    'allowed_filters'=>[
      "shop_id",
      "source_product_id",
      "direct",
      "source_marketplace",
      "status",
      "title",
      "sku",
    ]
];
