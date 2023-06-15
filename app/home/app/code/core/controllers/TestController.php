<?php

namespace App\Core\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\View\Engine\Volt\Compiler as VoltCompiler;

class TestController extends BaseController
{
    public static $appList = null;

    private function prepareProperty()
    {
        $userId = 3;
        $response = $this->di->getObjectManager()->get('\App\Hubspot\Components\Helper')->syncAllData($userId);
        print_r($response);
        die;

        //        $domain = $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->urlToDomain('http://192.168.0.49/mg223/');
        //        var_dump($domain);die;
        //        $response = $this->di->getObjectManager()->get('\App\Magento\Components\Helper')->getAttributesForFeed('', 3, [], 'google');
        //        print_r(array_keys($response['products'][0]));die;
        // $userId = 3;
        // $response = $this->di->getObjectManager()->get('\App\Hubspot\Components\Helper')->syncAllData($userId);
        // print_r($response);die;

        $property = $this->di->getObjectManager()->get('\App\Hubspot\Components\Properties');
        $prop_details = [];
        $prop_details["enabled"] = true;
        $prop_details["importOnInstall"] = true;
        $prop_details["productSyncSettings"] = [
            "properties" => $property->getGroupProperty('PRODUCT')
        ];
        $prop_details["dealSyncSettings"] = [
            "properties" => $property->getGroupProperty('DEAL')
        ];
        $prop_details["lineItemSyncSettings"] = [
            "properties" => $property->getGroupProperty('LINE_ITEM')
        ];
        $prop_details["contactSyncSettings"] = [
            "properties" => $property->getGroupProperty('CONTACT')
        ];
        return json_encode($prop_details);
    }

    public function loadAreaConfigurations() {
        $helper = $this->di->getObjectManager()->get('App\Core\Components\Helper');
        $config = $this->di->getCache()->get('config');
        foreach ($helper->getAllModules() as $module => $active) {
            if ($active) {
                $filePath = CODE . DS . $module . DS . 'etc' . DS . 'rest'.DS.'config.php';
                if (file_exists($filePath)) {
                    $array = new \Phalcon\Config\Adapter\Php($filePath);
                    $config->merge($array);
                }
                $systemConfigfilePath = CODE . DS . $module . DS . 'etc'  . DS . 'rest'.DS. 'webapi.php';
                if (file_exists($systemConfigfilePath)) {
                    $array = new \Phalcon\Config\Adapter\Php($systemConfigfilePath);
                    $config->merge($array);
                }
            }
        }
        $this->di->set('config', $config);
    }

    public function mongoAction()
    {
        $config = [
            [
                "key" => "shipping",
                "value" => "enabled",
                "group_code" => "order",
                // "source" => "shopify",
                // "target" => "amazon",
                // "source_shop_id" => 425,
                // "target_shop_id" => 465,
                "source_warehouse_id" => 458,
                "target_warehouse_id" => 465,

            ]
        ];
        echo "<pre>";
        $objectManager = $this->di->getObjectManager();
      $helper = $objectManager->get('\App\Core\Models\Config\Config');
      $res = $helper->setConfig($config);
      print_r($res);
      die();
        // start of guzzle test

        // try{
        //     $this->loadAreaConfigurations();
        //     print_r($this->di->getConfig()->get('marketplace-modules')->get('ebay1'));die;
        //     $client = $this->di->getObjectManager()->create('\App\Connector\Components\ApiClient');
        //     $newModel = $client->call('http://192.168.0.222/phalcon/ebay/public/webapi/rest/v1/product', ['ShopId'=>11]);
        //     echo $newModel->getStatusCode();
        //     echo $newModel->getReasonPhrase();
        //     print_r(json_decode($newModel->getBody()->getContents(), true));
        // } catch (\Exception $e){
        //     print_r($e->getTraceAsString());
        //     echo $e->getMessage();die('catch');
        // }
        // die;
        // end of guzzle test.

        // $database = $this->di->getConfig()->databases->db_mongo;
        // $dsn = 'mongodb://' . $database['host'];
        // $mongo = new \MongoDB\Client($dsn,array("username" => $database['username'], "password" =>$database['password']));
        // $collection = $mongo->ebay->user_details;

        // print_r($collection->find()->toArray());die;
        $model = $this->di->getObjectManager()->create('\App\Core\Models\User\Details');
        // $model->setData(["_id"=>1, 'user_id' => 3,'shops' => [["name"=>'shop1','_id'=>3]]]);
        // $model->save();

         $collection = $model->getCollection();
     //   $collection = $model->getPhpCollection();

        
        //$collection->updateOne(['_id'=>"1"],['$set'=>['shops.4'=>["name"=>'shop1']]]);
        //$collection->updateOne(['_id'=>3],['$set'=>['shops'=>[["name"=>'shop3',"id"=>3]]]]);
        //$collection->updateOne(['_id'=>3],['$push'=>['shops'=>["name"=>'shop4',"id"=>4]]]);
        //$collection->updateOne(['_id'=>3,'shops'=>['$elemMatch'=>['name'=>'shop4']]],['$set'=>['shops.$.name'=>'name5']]);
        die;
        $mongo = $this->di->getObjectManager()->create('\App\Connector\Models\Order');
        print_r($mongo->getOrders([
            'filter'=>[
                'target_order_id' => ''
            ],
            'count'=> 500
        ],true));die;

        $model = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $model->setSource('product_container_2');

        $aggregation = [];
        $aggregation[] = ['$unwind'=>'$variants'];

        $aggregation[] = [
            '$lookup'=> [
                'from'=> 'shopify_product_2',
                'localField'=> "_id",    // field in the product1 collection
                'foreignField'=> "_id",  // field in the product2 collection
                'as'=> "fromItems"
            ]
        ];

        $aggregation[] = [
            '$replaceRoot'=> [ 'newRoot'=> [ '$mergeObjects'=> [ [ '$arrayElemAt'=> [ '$fromItems', 0 ] ], '$$ROOT' ] ] ]
        ];
        $aggregation[] = ['$match'=>[
            '$and' => [
               [
                   'store_id' => '2',
               ],
                [
                    '$or' => [
                        [
                            '$and'=>[
                                [
                                    'variants.price'=>[
                                        '$gt'=>1
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
        ];
        $collection = $model->getCollection()->aggregate($aggregation);
        print_r($collection->toArray());die;
        $user_id = 2;
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $mongo->setSource("product_container_".$user_id);

        $collection = $mongo->getCollection();
        $collection->createIndex(["details.source_product_id"=> 1]);
        $collection->createIndex(["variants.source_variant_id"=> 1]);
        $collection->createIndex([
            "details.title"=> "text",
            "details.short_description"=> "text",
            "details.long_description"=> "text"
        ]);

        $data = [
            'details' => [
                'source_product_id' => 'source_product_'.time(),
                'title' => 'test new',
                'short_description' => 'working on it',
                'long_description' => 'aganing a new test',
                'type' => 'simple'
            ],
            'variant_attribute' => [],
            'variants' => [
                [
                    'source_variant_id' => 'source_variant_'.time(),
                    'sku' => 'sku_'.time(),
                    'price' => '15',
                    'quantity' => '10',
                    'position' => '1',
                    'weight' => '15',
                    'weight_unit' => 'grams',
                    'dimensions' => 'test_1223',
                    'upc' => 'upc_'.time(),
                    'size' => 'small',
                    'warehouse' => [
                        ['id' => '3', 'qty' =>'5']
                    ]
                ],
            ]
        ];
        $container = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        $result = $container->createProduct($data);
        print_r($result);die;
        //$m = new \MongoDB\Driver\Manager("mongodb://root@password@localhost:27017/test");
        // print_r($m->getServers());die;
        //new \App\Core\Models\Test();
        $model = $this->di->getObjectManager()->create('\App\Core\Models\Test');
        $model->setSource('product2');
        $data = $model->getCollection()->aggregate([
            [
                '$lookup'=> [
                    'from'=> "product1",
                    'localField'=> "sku",    // field in the product1 collection
                    'foreignField'=> "sku",  // field in the product2 collection
                    'as'=> "fromItems"
                ]
            ],
            [
                '$replaceRoot'=> [ 'newRoot'=> [ '$mergeObjects'=> [ [ '$arrayElemAt'=> [ '$fromItems', 0 ] ], '$$ROOT' ] ] ]
            ],
            [ '$project'=> ['fromItems'=> 0 ] ]
        ]);
        $collections = [];
        foreach ($data as $document) {
            /**
             * Assign the values to the base object
             */
            $collections[] = $document;
        }
        print_r($collections);die;
        //$model->setSource('product1');
        //// $model->getCollection()->createIndex( ['sku'=>1], ['unique'=>true ] );
        //$model->setData(['name'=>'product-name1','sku'=>'product-sku-1']);
        // $model->save();

        /*
                $model = $this->di->getObjectManager()->create('\App\Core\Models\Test');
                $model->setSource('product2');
                $model->getCollection()->createIndex( ['sku'=>1], ['unique'=>true ] );
                $model->setData(['name'=>'product-name1','sku'=>'product-sku-1','qty'=>10]);
                $model->save();
        */

        // $data = \App\Core\Models\Test::findFirst();
        // var_dump($data->toArray());
        //$model = new \App\Core\Models\Test();
        die('http://192.168.0.222/phalcon/importer/public/user/login');
    }

    public function indexAction()
    {
        $sqs = $this->di->getObjectManager()->get('\App\Core\Components\Message\Handler\Sqs');
        $sqs->pushMessage([
            'queue_name'=>'test-queue',
            'data' => ' Enjoyyy LLiiiffeee !!! updated'
        ]);
        die('done');
        $this->di->getObjectManager()->get('\App\Core\Components\Message\Handler\MongoAndRmq')->queueFutureMessages();
        $handlerData = [
            'type' => 'full_class',
            'class_name' => '\App\Core\Components\Helper',
            'method' => 'testingThrottle',
            'queue_name' => 'test_queue',
            'own_weight' => 100,
            'user_id' => 1,
            'run_after' => time()+180,
            'data' => ' Enjoyyy LLiiiffeee !!! updated'
        ];
        $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
        $response = $helper->createQueue($handlerData['queue_name'],$handlerData);

        die;
        $desc = '<ul> <li> <h5>Top Solids Yarn: Roving Weight Yarn, 100% Merino Wool Yarn</h5> </li> <li> <h5>Attributes: 21.5 Micron Count</h5> </li> <li> <h5>Used For: Spinning, Felting, and Crafts</h5> </li> <li> <h5>Wt / Yardage: 4 ounces, Approximately 3 yards</h5> </li> <li> <h5>Texture / Care: Hand Dyed Roving, Hand Wash, Cool Water</h5> </li> </ul> <p>NOTE! Some colors may not show up on your computer as they actually are.</p> <p><span style="background-color: transparent; font-variant-numeric: normal; font-variant-east-asian: normal; vertical-align: baseline;"><span style="font-size: 15.3333px; white-space: pre-wrap;">Merino wool roving for your felting and spinning projects. Merino wool is the easiest fiber to felt and yields outstanding results. Spinning roving weight with Merino wool yarn produces a superb fine yarn for knitting. </span></span></p>';
        $pattern = '~<li(.*?)</li>~s';
        preg_match_all($pattern, $desc, $matches);
        foreach ($matches as $key => $value) {
            $matches[$key] = strip_tags($value);
        }
        $productHighlight = implode(',', $matches);
        $productHighlight = substr($productHighlight, 0, 150);
        print_r(implode(',', $matches));die;
        $substr = substr($desc, strpos($desc, '<ul'), strpos($desc, '</ul>') - strpos($desc, '<ul'));
        echo $substr;die;

        $userId = 734;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $mongo->setSource("product_container_" . $userId);
        $title = ['Runner Runner (Blu-ray/DVD, 2014, 2-Disc Set)'];
        $filetrQuery =[
                    'details.title' => [
                        '$in' => $title
                    ],
                ];
        $finalQuery = [

            [
                '$match' => $filetrQuery
            ],
            [
                '$project' => [
                    'details.source_product_id' => 1,
                    'details.title' => 1
                ]
            ],
        ];
        $attributes = $mongo->getCollection()->aggregate($finalQuery);
        print_r($attributes);die;
        
        $shopify = \App\Shopify\Models\Shop\Details::find();
        foreach ($shopify as $key => $value) {
            $userId = $value->user_id;
            $shopName = $value->shop_url;
            $token = $value->token;
            $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                            ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                        ]);
            // $this->di->getObjectManager()->get('App\Shopify\Components\Helper')->createNecessaryWebhooks($shopName, $userId);
            $webhooks = $this->di->getObjectManager()->get('App\Shopify\Components\WebhookHelper')->getExistingWebhooks($shopName, $userId);
            foreach ($webhooks[0] as $value) {
                if (!isset($value["errors"]) &&
                    strpos($value['address'], 'https://shoppingfeed.cedcommerce.com/api') !== false) {
                    print_r($shopName);break;
                    // $shopifyClient->call('DELETE', '/admin/webhooks/'.$value["id"].'.json');
                }
            }
            /*$webhooks = $this->di->getObjectManager()->get('App\Shopify\Components\WebhookHelper')->getExistingWebhooks($shopName, $userId);*/
        }

        phpinfo();
        $obj = new \SimpleXMLElement('<root>
                        <a>1.9</a>
                        <b>1.9</b>
                    </root>');
        print_r($obj);die;

        
        $date = '2019-02-22 12:00:00';
        $period = 7;
        $response = $this->di->getObjectManager()->get('\App\Frontend\Components\Helper')->getPlanExpirationDate($date, $period);
        print_r($response);die;

        for ($i = 0; $i < 5; $i++) { 
            $handlerData = [
                'type' => 'full_class',
                'class_name' => '\App\Core\Components\Helper',
                'method' => 'testingThrottle',
                'queue_name' => 'test_queue',
                'own_weight' => 100,
                'user_id' => 1,
                'message_unique_key' => $i,
                'data' => ' Enjoyyy LLiiiffeee !!! updated'
            ];
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            $response = $helper->createQueue($handlerData['queue_name'],$handlerData);
        }
        die;

        /*$userId = 194;
        $shopName = 'for-fine-fragrances.myshopify.com';
        $token = '89a905fce4c9e13e287f8f6348724943';
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('order_' . $userId);
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        $orders = $collection->find([]);
        foreach ($orders as $key => $value) {
            $orderId = $value->source_order_id;
            $orderValue = $collection->findOne([
                    'source_order_id' => $orderId
                ], ["typeMap" => ['root' => 'array', 'document' => 'array']]);
            $shippingLines = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getShippingLines($orderValue);
            $orderData = [
                'tax_lines' => isset($orderValue['tax_lines']) ? $orderValue['tax_lines'] : [],
                'email' => $orderValue['client_details']['contact_email'],
                'browser_ip' => $orderValue['client_details']['browser_ip'],
                'currency' => $orderValue['currency'],
                'total_tax' => $orderValue['total_tax'],
                'client_details' => [
                    'browser_ip' => $orderValue['client_details']['browser_ip']
                ],
                'created_at' => $orderValue['placed_at'],
                'fulfillment_status' => isset($orderValue['fulfillment_status']) ? $orderValue['fulfillment_status'] : 'unfulfilled',
                'processed_at' => $orderValue['placed_at'],
                'subtotal_price' => $orderValue['subtotal_price'],
                'taxes_included' => false,
                'total_discounts' => $orderValue['total_discounts'],
                'total_price' => $orderValue['total_price'],
                'total_tax' => $orderValue['total_tax'],
                'total_weight' => $orderValue['total_weight'],
                'financial_status' => 'paid',
                'inventory_behaviour' => 'decrement_obeying_policy',
                'note' => 'CedCommerce created this order by Google Express Integration App. Google order id => ' . $orderValue['source_order_id'],
                'transactions' => [
                    [
                        'kind' => 'sale',
                        'status' => 'success',
                        'amount' => $orderValue['total_price']
                    ]
                ]
            ];
            
            if (isset($orderValue['discount_codes'])) {
                $orderData['discount_codes'] = $orderValue['discount_codes'];
            }
            if (isset($orderValue['client_details']['contact_email']) &&
                isset($orderValue['client_details']['name'])) {
                $orderData['customer'] = [
                    'email' => $orderValue['client_details']['contact_email'],
                    'first_name' => $orderValue['client_details']['name']
                ];
                $phoneNumber = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getPhoneNumber($orderValue, $userId);
                if ($phoneNumber) {
                    $orderData['customer']['phone'] = $phoneNumber;
                }
            }
            $lineItems = [];
            foreach ($orderValue['line_items'] as $itemKey => $itemValue) {
                $quantityOrdered = $itemValue['quantity_ordered'] - $itemValue['quantity_canceled'];
                if ($quantityOrdered > 0) {
                    $itemData = [
                        'fulfillable_quantity' => $quantityOrdered,
                        'quantity' => $quantityOrdered,
                        'sku' => $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getSkuForLineItem($itemValue, $userId),
                        'title' => $itemValue['title'],
                        'price' => (string)$itemValue['unit_price'],
                        'total_discount' => $itemValue['total_discount'],
                        'fulfillment_status' => $itemValue['fulfillment_status'],
                        'grams' => $itemValue['weight']
                    ];
                    $variantId = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getVariantId($itemValue, $userId);
                    $vendor = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getVendor($itemValue, $userId);
                    $productId = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getProductId($itemValue, $userId);
                    if ($variantId &&
                        $productId) {
                        $itemData['variant_id'] = $variantId;
                        $itemData['product_id'] = $productId;
                        $baseSku = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getSkuFromBase($productId, $variantId, $userId);
                        if ($baseSku) {
                            $itemData['sku'] = $baseSku;
                        }
                    }
                    if ($vendor) {
                        $itemData['vendor'] = $vendor;
                    }
                    $lineItems[] = $itemData;   
                }
            }
            if (count($lineItems) > 0) {
                $orderData['line_items'] = $lineItems;
                $billingAddress = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getBillingAddress($orderValue);
                $deliveryAddress = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getDeliveryAddress($orderValue);
                if ($billingAddress &&
                    $deliveryAddress) {
                    $orderData['billing_address'] = $billingAddress;
                    $orderData['shipping_address'] = $deliveryAddress;
                }
                if ($shippingLines) {
                    $orderData['shipping_lines'] = $shippingLines;
                }
                $orderData = [
                        'order' => $orderData
                    ];
                // print_r(json_encode($orderData));die;
                $response = $shopifyClient->call('POST', '/admin/orders.json', $orderData);
                // print_r(json_encode($response));die;
                if (!isset($response['errors'])) {
                    $res = $collection->findOneAndUpdate([
                        'source_order_id' => $orderId
                    ], ['$set' => ['target_order_id' => (string)$response['id']]]);
                } else {
                    print_r('Error in order ID => ' . $orderId);
                    print_r($response);
                }   
            }
        }
        die;*/


        /*$userId = 170;
        $sourceProductId = '2096076259387';
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('product_container_' . $userId);
        $productData = $collection->findOne([
                "details.source_product_id" => (string)$sourceProductId
            ], ["typeMap" => ['root' => 'array', 'document' => 'array']]);

        $userId = 2;
        $sourceShopId = 7;
        $queryDetails = [
                'marketplace' => 'source', 
                'query' => '(price > -1)', 
                'activePage' => 1, 
                'count' => 100
            ];
        $profileId = 4;
        $productsData = $this->di->getObjectManager()->get('\App\Connector\Models\Product')->getProductsByQuery($queryDetails, $userId);
        $productsData = $productsData['data'];
        $source = 'shopify';*/
        // $mappedProducts = $this->di->getObjectManager()->get('\App\Google\Components\UploadHelper')->mapProductsByDefaultProfile($productsData, $source, $userId);

        /*$mappedProducts = $this->di->getObjectManager()->get('\App\Google\Components\UploadHelper')->mapProductsByCustomProfile($productsData, $profileId, $source);
        print_r(json_encode($mappedProducts));die;*/

        foreach ($shops as $key => $value) {
            sleep(1);
            $shop = $value['shop_url'];
            $userId = $value['user_id'];
            $webhooks = $this->di->getObjectManager()->get('App\Shopify\Components\WebhookHelper')->getExistingWebhooks($shop, $userId);
            if (!$webhooks) {
                continue;
            }
            if (count($webhooks[0]) < 2) {
                $configData = \App\Core\Models\User\Config::findFirst(["user_id='{$userId}' AND path='/App/User/Step/ActiveStep' AND value=4"]);
                if ($configData) {
                    $this->di->getObjectManager()->get('App\Shopify\Components\Helper')->createNecessaryWebhooks($shop, $userId);   
                }
            }
        }

        $planData = [
            'connectors' => 'shopify',
            'total_credits' => 5000,
            'services' => [
                [
                    'code' => 'product_import',
                    'merchant_id' => 434,
                    'type' => 'importer',
                    'charge_type' => 'prepaid'
                ]
            ]
        ];
        print_r(json_encode($planData));die;

        $userId = 99;
        $shopName = 'beautykindgives.myshopify.com';
        $token = '23a35e3f0f6094035cc776d876530be9';
        $beautyKindDevShopifyOrders = ['900290838613', '900290871381', '900290936917', '900291035221', '900291067989', '900291100757', '900291133525', '900291166293', '900291199061', '900291264597', '900291297365', '902679101525', '902896484437', '902954680405', '904045166677'];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('order_80');
        $responses = [];
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        foreach ($beautyKindDevShopifyOrders as $key => $targetOrderId) {
            $orderValue = $collection->findOne(
                [
                    "source_order_id" => $targetOrderId
                ],
                ["typeMap" => ['root' => 'array', 'document' => 'array']]
            );
            $shippingLines = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getShippingLines($orderValue);
            $orderData = [
                'tax_lines' => isset($orderValue['tax_lines']) ? $orderValue['tax_lines'] : [],
                'email' => $orderValue['client_details']['contact_email'],
                'browser_ip' => $orderValue['client_details']['browser_ip'],
                'currency' => $orderValue['currency'],
                'total_tax' => $orderValue['total_tax'],
                'client_details' => [
                    'browser_ip' => $orderValue['client_details']['browser_ip']
                ],
                'created_at' => $orderValue['placed_at'],
                'fulfillment_status' => isset($orderValue['fulfillment_status']) ? $orderValue['fulfillment_status'] : 'unfulfilled',
                'processed_at' => $orderValue['placed_at'],
                'subtotal_price' => $orderValue['subtotal_price'],
                'taxes_included' => $orderValue['taxes_included'],
                'total_discounts' => $orderValue['total_discounts'],
                'total_price' => $orderValue['total_price'],
                'total_tax' => $orderValue['total_tax'],
                'total_weight' => $orderValue['total_weight'],
                'financial_status' => 'paid',
                'inventory_behaviour' => 'decrement_obeying_policy',
                'note' => 'CedCommerce created this order by Google Express Integration App. Source order id => ' . $orderValue['source_order_id']
            ];
            if (isset($orderValue['discount_codes'])) {
                $orderData['discount_codes'] = $orderValue['discount_codes'];
            }
            if (isset($orderValue['client_details']['contact_email']) &&
                isset($orderValue['client_details']['name'])) {
                $orderData['customer'] = [
                    'email' => $orderValue['client_details']['contact_email'],
                    'first_name' => $orderValue['client_details']['name']
                ];
                $phoneNumber = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getPhoneNumber($orderValue, $userId);
                if ($phoneNumber) {
                    $orderData['customer']['phone'] = $phoneNumber;
                }
            }
            $lineItems = [];
            foreach ($orderValue['line_items'] as $itemKey => $itemValue) {
                $quantityOrdered = $itemValue['quantity_ordered'] - $itemValue['quantity_canceled'];
                if ($quantityOrdered > 0) {
                    $itemData = [
                        'fulfillable_quantity' => $quantityOrdered,
                        'quantity' => $quantityOrdered,
                        'sku' => $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getSkuForLineItem($itemValue, $userId),
                        'title' => $itemValue['title'],
                        'price' => (string)$itemValue['unit_price'],
                        'total_discount' => $itemValue['total_discount'],
                        'fulfillment_status' => $itemValue['fulfillment_status'],
                        'grams' => $itemValue['weight']
                    ];
                    $variantId = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getVariantId($itemValue);
                    $productId = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getProductId($itemValue);
                    if ($variantId &&
                        $productId) {
                        $itemData['variant_id'] = $variantId;
                        $itemData['product_id'] = $productId;
                        $baseSku = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getSkuFromBase($productId, $variantId, $userId);
                        if ($baseSku) {
                            $itemData['sku'] = $baseSku;
                        }
                    }
                    $lineItems[] = $itemData;   
                }
            }
            $orderData['line_items'] = $lineItems;
            $billingAddress = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getBillingAddress($orderValue);
            $deliveryAddress = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getDeliveryAddress($orderValue);
            if ($billingAddress &&
                $deliveryAddress) {
                $orderData['billing_address'] = $billingAddress;
                $orderData['shipping_address'] = $deliveryAddress;
            }
            if ($shippingLines) {
                $orderData['shipping_lines'] = $shippingLines;
            }
        }
        print_r(json_encode($responses, true));die;

        for ($i = 0; $i < 5; $i++) { 
            $handlerData = [
                'type' => 'full_class',
                'class_name' => '\App\Core\Components\Helper',
                'method' => 'testingThrottle',
                'queue_name' => 'test_queue',
                'own_weight' => 100,
                'user_id' => 1,
                'data' => ' Enjoyyy LLiiiffeee !!! '
            ];
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            $response = $helper->createQueue($handlerData['queue_name'],$handlerData);
        }

        print_r($response);
        print_r('current time => ' . time());die;
        $path = BP . DS . 'var' . DS . 'equipment-data.csv';
        $file = fopen($path, 'r');
        $productData = [];
        while ($productData[] = fgetcsv($file, 1024, ',')) {
            
        }
        $queryArray = [];
        for ($i = 1; $i < count($productData); $i++) { 
            if ($productData[$i][3] == 'New') {
                $queryArray[] = '(title == ' . addslashes($productData[$i][1]) . ')';
            }
        }
        $query = '(' . implode(' || ', $queryArray) . ')';
        print_r($query);die;

        $data = [
            'username' => 'Sudeep Mukherjee',
            'email' => 'sudeepmukherjee@cedcommerce.com',
            'subject' => 'You have recieved a new order',
            'marketplace' => 'Google',
            'shopify_order_id' => 'shfjdfkd56654654kjk',
            'marketplace_order_id' => 'hht678ghj3dffgggfgdgf',
            'bccs' => ['sudeepmukherjee@cedcommerce.com', 'satyaprakash@cedcoss.com'],
            'line_items' => [
                [
                    'title' => 'Phillips Trimmer',
                    'quantity' => 6,
                    'unit_price' => '$65.00',
                    'total_price' => '$390.00'
                ],
                [
                    'title' => 'Alisa and Chloe Men\'s Jacket',
                    'quantity' => 2,
                    'unit_price' => '$500.00',
                    'total_price' => '$1000.00'
                ]
            ],
            'total_price' => '$1390.00',
            'billing_address' => [
                'Vishwas Khand, Gomti Nagar',
                'Lucknow, Uttar Pradesh',
                '+91 979-288-2621',
                'India'
            ],
            'customer' => [
                'name' => 'Sudeep Mukherjee',
                'email' => 'sudeepmukherjee@cedcommerce.com'
            ]
        ];
        $path = 'core' . DS . 'view' . DS . 'email' . DS . 'order_test.volt';
        $data['path'] = $path;
        $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
        die('done');
        for ($i = 0; $i < 50; $i++) { 
            $handlerData = [
                    'type' => 'full_class',
                    'class_name' => '\App\Core\Components\Helper',
                    'method' => 'testingThrottle',
                    'queue_name' => 'testing_throttle',
                    'own_weight' => 100,
                    'data' => []
                ];
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            $helper->createQueue($handlerData['queue_name'], $handlerData);
        }
        die('Ended');

        /*$mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('order_76');
        $orderData = $collection->findOne(
                [
                    "source_order_id" => 'G-SHP-1294-97-8244'
                ],
                ["typeMap" => ['root' => 'array', 'document' => 'array']]
            );
        $orderData = [$orderData];
        $this->di->getObjectManager()->get('App\Shopify\Models\SourceModel')->massUploadOrders($orderData);die('end');*/
        
        /*$ordersToCreate = [
                'G-SHP-1294-97-8244'
            ];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('order_76');
        $shopName = 'strictly-mosaic-store.myshopify.com';
        $token = '7bae05447cd5bfd4320b2d96d9557b86';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        foreach ($ordersToCreate as $key => $value) {
            $orderValue = $collection->findOne(
                [
                    "source_order_id" => $value
                ],
                ["typeMap" => ['root' => 'array', 'document' => 'array']]
            );
            $orderData = [
                'tax_lines' => isset($orderValue['tax_lines']) ? $orderValue['tax_lines'] : [],
                'email' => $orderValue['client_details']['contact_email'],
                'browser_ip' => $orderValue['client_details']['browser_ip'],
                'currency' => $orderValue['currency'],
                'total_tax' => $orderValue['total_tax'],
                'client_details' => [
                    'browser_ip' => $orderValue['client_details']['browser_ip']
                ],
                'created_at' => $orderValue['placed_at'],
                'fulfillment_status' => isset($orderValue['fulfillment_status']) ? $orderValue['fulfillment_status'] : 'unfulfilled',
                'processed_at' => $orderValue['placed_at'],
                'subtotal_price' => $orderValue['subtotal_price'],
                'taxes_included' => $orderValue['taxes_included'],
                'total_discounts' => $orderValue['total_discounts'],
                'total_price' => $orderValue['total_price'],
                'total_tax' => $orderValue['total_tax'],
                'total_weight' => $orderValue['total_weight'],
                'note' => 'CedCommerce created this order by Google Express Integration App. Target order id => ' . $orderValue['source_order_id']
            ];
            if (isset($orderValue['discount_codes'])) {
                $orderData['discount_codes'] = $orderValue['discount_codes'];
            }
            if (isset($orderValue['client_details']['contact_email']) &&
                isset($orderValue['client_details']['name'])) {
                $orderData['customer'] = [
                    'email' => $orderValue['client_details']['contact_email'],
                    'first_name' => $orderValue['client_details']['name']
                ];
            }
            $lineItems = [];
            foreach ($orderValue['line_items'] as $itemKey => $itemValue) {
                $lineItems[] = [
                    'fulfillable_quantity' => $itemValue['quantity_ordered'],
                    'quantity' => $itemValue['quantity_ordered'] - $itemValue['quantity_canceled'],
                    'sku' => $this->getSkuForLineItem($itemValue, 71),
                    'title' => $itemValue['title'],
                    'price' => (string)$itemValue['unit_price'],
                    'total_discount' => $itemValue['total_discount'],
                    'title' => $itemValue['title'],
                    'fulfillment_status' => $itemValue['fulfillment_status'],
                    'grams' => $itemValue['weight']
                ];
            }
            $orderData['line_items'] = $lineItems;
            $billingAddress = $this->getBillingAddress($orderValue);
            if ($billingAddress) {
                $orderData['billing_address'] = $billingAddress;
                $orderData['shipping_address'] = $billingAddress;
            }
            $orderData = [
                'order' => $orderData
            ];
            // print_r($orderData);die;
            $response = $shopifyClient->call('POST', '/admin/orders.json', $orderData);
            print_r(json_encode($response));
        }
        die('end');*/
        
        $shopName = 'united-salon.myshopify.com';
        $token = 'f15fd5f6bd5706474f3f4914f8189599';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        $response = "";
        $params = [
            'created_at_min' => '2018-12-05T00:00:00-00:00'
        ];
        $orderId = '715699028003';
        $response = $shopifyClient->call('GET', '/admin/orders/' . $orderId . '.json', []);
        print_r(json_encode($response));die('end');
        
        
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('order_71');
        $orderData = $collection->findOne(
                [
                    "source_product_id" => 'G-SHP-7951-43-9367'
                ],
                ["typeMap" => ['root' => 'array', 'document' => 'array']]
            );
        print_r($orderData);die;
        $service = new \Google_Service_ShoppingContent($client);
        $reponse = $service->orders->get(105741647, 'G-SHP-7951-43-9367');
        print_r($reponse);
        die('done');
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        $count = 0;
        $shopName = 'united-salon.myshopify.com';
        $token = 'f15fd5f6bd5706474f3f4914f8189599';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        $response = "";
        $params = [
            'created_at_min' => '2018-12-03T00:00:00-00:00'
        ];
        $response = $shopifyClient->call('GET', '/admin/orders.json', $params);
        print_r($response);die('end');

        $shopName = 'salttree.myshopify.com';
        $token = '15fbca36718b786979d7630e681c1443';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
        $response = "";
        $params = [];
        $response = $shopifyClient->call('GET', '/admin/recurring_application_charges.json', $params);
        print_r($response);die;

        $sourceShopId = 102;
        $userId = 65;
        $merchantId = 111793079;
        $queryDetails = [
                        'marketplace' => 'shopify', 
                        'query' => '(price > -1)',
                        'activePage' => 1, 
                        'count' => 1000
                    ];
        $productsData = $this->di->getObjectManager()->get('\App\Connector\Models\Product')->getProductsByQuery($queryDetails, $userId, $sourceShopId);
        print_r($productsData[0]['shop_id']);die;
        $mappedProducts = $this->di->getObjectManager()->get('\App\Google\Components\UploadHelper')->mapProductsByDefaultProfile($productsData, 'shopify', $userId);
        $productBatchData = $this->prepareProductBatch($mappedProducts, $merchantId);
        $helper = $this->di->getObjectManager()->get('\App\Google\Components\Helper');
        $client = $helper->getGoogleClient($userId, $merchantId);
        $result = $helper->batchProcess($client,$productBatchData);
        print_r($result);
        die('success');
        $file = fopen($filePath, 'r');
        $productData = [];
        while ($productData[] = fgetcsv($file, 1024, ',')) {
            continue;   
        }
        $query = '';
        for ($i = 1; $i < count($productData); $i++) {
            if ($productData[$i][3] == 'New') {
                $query .= ' (title' . ' == ' . addslashes($productData[$i][1]) . ') ||';   
            }
        }
        print_r($query);die;

        $shopId = 1;
        $userId = 3;
        $googleShop = \App\Google\Models\Shop\Details::findFirst(["id='{$shopId}'"]);
        $googleMerchantId = $googleShop->merchant_id;

        $helper = $this->di->getObjectManager()->get('\App\Google\Components\Helper');

        $client = $helper->getGoogleClient($userId,$googleMerchantId);

        $service = $helper->getShoppingContentService($client);
        // @TODO Uncomment line after testing
        $parameters = ['acknowledged'=>true];
        $parameters = [];
        $orders = $service->orders->listOrders($googleMerchantId,$parameters);
        print_r($orders);die;

        print_r($this->di->getObjectManager()->get('\App\Frontend\Components\AdminHelper')->getAllShops(['db' => 'db']));die;
        $tempUser = [
            'username' => 'testing123',
            'email' => 'cedcommerce@gmail.com',
            'password' => '123456',
            'role_id' => 3,
            'confirmation' => 1,
            'status' => 2
        ];
        $newUser = $this->di->getObjectManager()->create('\App\Shopify\Models\User');
        $newUserSaveStatus = $newUser->createUser($tempUser, 'customer', true);
        var_dump($newUserSaveStatus);die;
        // $tempData = [
        //     '14085453938111' => [
        //         'status' => 'uuu'
        //     ]
        // ];
        // $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        // $collection = $mongo->getCollectionForTable('shopify_product_2');
        // $productData = $collection->findOne(
        //         [
        //             "_id" => '71',
        //             "import" => true
        //         ],
        //         ["typeMap" => ['root' => 'array', 'document' => 'array']]
        //     );
        // print_r($productData['variants']);
        // print_r('<br>');
        // foreach ($productData['variants'] as $key => $value) {
        //     if (!isset($tempData[$key])) {
        //         $tempData[$key] = $value;
        //     }
        // }
        // print_r($tempData);die('jkl');

        /*$data = [
            'data' => [
                'target' => 'shopify'
            ]
        ];
        $this->di->getObjectManager()->get('\App\Connector\Components\OrderHelper')->syncOrderToTarget($data);
        die('dcfv');
        for ($i = 0; $i < 500; $i++) { 
            $handlerData = [
                    'type' => 'class',
                    'class_name' => 'Qhandler',
                    'method' => 'test',
                    'queue_name' => 'test_queue',
                    'own_weight' => 100,
                    'data' => []
                ];
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            $helper->createQueue($handlerData['queue_name'], $handlerData);
        }
        die('Ended');*/

        $sid = 'AC259f140d36d6fbb8a9b32eeafd246c18';
        $token = '70198f0f9cf3938b64a298a55ae39f09';
        $client = new \Twilio\Rest\Client($sid, $token);

        // Use the client to do fun stuff like send text messages!
        $client->messages->create(
            // the number you'd like to send the message to
            '+919792882621',
            array(
                // A Twilio phone number you purchased at twilio.com/console
                'from' => '+12253417074',
                // the body of the text message you'd like to send
                'body' => 'Hey Sudeep! How are you?'
            )
        );
        die('success');

        $shop = 'testing125-ced.myshopify.com';
        $userId = 2;
        $response = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->massUploadOrders('', $shop);
        die('success');
        /*$shop = 'sellernext.myshopify.com';
        $userId = 2;
        $response = $this->di->getObjectManager()->get('\App\Shopify\Components\WebhookHelper')->getExistingWebhooks($shop, $userId);*/
        /*$shop = 'sellernext.myshopify.com';
        $userId = 2;
        $response = $this->di->getObjectManager()->get('\App\Shopify\Components\WebhookHelper')->createWebhooks($shop, $userId);*/
        print_r($response);die;

        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $this->di->getConfig()->get('mailer')->get('smtp')->get('host');  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $this->di->getConfig()->get('mailer')->get('smtp')->get('username');;                // SMTP username
        $mail->Password = $this->di->getConfig()->get('mailer')->get('smtp')->get('password');                           // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $this->di->getConfig()->get('mailer')->get('smtp')->get('port');                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($this->di->getConfig()->get('mailer')->get('sender_email'), $this->di->getConfig()->get('mailer')->get('sender_name'));
        $mail->addAddress('satyaprakash@cedcoss.com', 'Joe User');     // Add a recipient
        /*$mail->addAddress('ellen@example.com');               // Name is optional
        $mail->addReplyTo('info@example.com', 'Information');
        $mail->addCC('cc@example.com');*/
        $mail->addBCC($this->di->getConfig()->get('mailer')->get('bcc'));


        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Here is the subject';
        $mail->Body    = 'This is the HTML message body <b>in bold!dfsgdfsg</b>';
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        var_dump($mail->send());
        die('sent');

        $userId = 3;

        $magentoResponse = $this->di->getObjectManager()->get('\App\Hubspot\Components\Helper')
            ->syncAllData($userId);
        //    $response  = json_decode($magentoResponse, true);
        var_dump($magentoResponse);
        die;
        $hubspotClient = $this->di->getObjectManager()->get('\App\Hubspot\Components\ConnectionManager');

        $hubspotClient->setUserId($userId);
        //    $result = $hubspotClient->hubspot_validate_oauth_token();
        $result = $hubspotClient->get_mapp_setting();
        //    $result = $hubspotClient->insert_or_update_setting($this->prepareProperty());
        var_dump($result);
        die;

        $hubspotClient = $this->di->getObjectManager()->get('\App\Hubspot\Components\ConnectionManager')
                        ->setUserId($userId);
        $result = $hubspotClient->get_mapp_setting();
        //    $result = $hubspotClient->insert_or_update_setting($this->prepareProperty());
        print_r(json_decode($result['response'], true));die;

        $uri = 'http://192.168.0.49/mg223/rest/all/V1/feed-api/getProduct?searchCriteria[pageSize]=2&searchCriteria[currentPage]=7';
        $params = [
            'searchCriteria[pageSize]' => 2,
            'searchCriteria[currentPage]' => 1
        ];
        $response = $this->di->getObjectManager()->get('\App\Magento\Components\Helper')->sendRequest(3, 'rest/V1/feed-api/getUpdatedProduct');
        print_r($response);
        die('gggg');

        $endpoint = 'rest/V1/hub-api/getProduct';
        $response = $this->di->getObjectManager()->get('\App\Magento\Components\Helper')->sendRequest($userId, $endpoint);
        print_r($response);
        die('the end');
        /*$magentoDetails = [
            'oauth_consumer_key' => 'ph3o4a7x4yfl3h9van1pcuhatsd4p1gk'
        ];
        $response = $this->di->getObjectManager()->get('\App\Magento\Models\MagentoShopDetails')->checkLogin($magentoDetails);
        print_r($response);
        die('temp');*/
        /*$path = '/var/www/html/engine/var/engine/feeds/facebookads/22/product_feed_1530082593.csv';*/
        /*$folderPath = substr($path, 0, strripos($path, '/'));
        print_r($folderPath);die;*/
        /*$this->di->getObjectManager()->get('\App\Engine\Models\GoogleBingShoppingFeedProducts')->exportFeedFile($path);
        die('fb test end');*/
        /*$this->di->getObjectManager()->get('\App\Core\Models\Notifications')->sendMessageToClient(17);
        die('Message send');*/
        /*$userId = 38;
        $shop = 'beauty-n-fashion.myshopify.com';
        $this->di->getObjectManager()->get('\App\Shopify\Components\Helper')->getAttributesForFeed($shop, $userId);
        die('got all data');*/
        /*$shopName = \App\Google\Models\Shop\Details::findFirst(["shop_url='bellagracestore.com'"]);
        $feedObj = \App\Engine\Models\ProductFeeds::findFirst(["id=178"]);
        $this->di->getObjectManager()->get('\App\Engine\Models\Google')->updateProductsOnMerchantCenter($shopName, $feedObj);
        die('All fine');*/
        /*$shopName = 'bella-grace-jewelry-and-gifts.myshopify.com';
        $token = '066af16edb98fb7c6f312b40f42f9fe7';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
                    $response = "";
                    $params = [
                        'title' => 'The Empire™ Platinum and 14K Solid Gold Bracelet'
                    ];
                    $response = $shopifyClient->call('GET', '/admin/products.json', $params);
                    print_r($response);
                    print_r('<br>');die('fff');*/
        /*$fileName = 'product_feed_6_1525938266.php';
        $data = $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->getFeedSettingsFileData($fileName, ['shopping_engine']);
        print_r($data);die;*/
        /*$shopName = 'bella-grace-jewelry-and-gifts.myshopify.com';
        $userId = 17;
        $productsDetails = $this->di->getObjectManager()->get('\App\Shopify\Components\Helper')->getAttributesForFeed($shopName, $userId);

        print_r($productsDetails);die;*/


        /*$charge_id = 185008216;
        $shopName = 'bella-grace-jewelry-and-gifts.myshopify.com';
        $token = '066af16edb98fb7c6f312b40f42f9fe7';
        $shopifyClient = $this->di->getObjectManager()->get('App\Shopify\Components\ShopifyClientHelper', [
                        ['type' => 'parameter', 'value' => $shopName], ['type' => 'parameter', 'value' => $token], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->auth_key], ['type' => 'parameter', 'value' => $this->di->getConfig()->security->shopify->secret_key]
                    ]);
                    $response = "";
                    $response = $shopifyClient->call('GET', '/admin/application_charges/' . $charge_id . '.json');
                    $response = $shopifyClient->call('POST', '/admin/application_charges/' . $charge_id . '/activate.json', $response);
                    print_r($response);die;*/


        /*$userId = 17;
        $merchantId = 116881196;
        $nextPageToken = false;
        $productIds = $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->getAllProductIdsMerchantCenter($userId, $merchantId, $nextPageToken);
        $nextPageToken = $productIds['nextPageToken'];
        $productIds = $productIds['products'];
        if (count($productIds)) {
            $deleteProducts = $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->deleteChunkOfProducts($productIds, $userId, $merchantId, 'google');
        }
        print_r('Total Products => ' . count($productIds) . '<br>');
        print_r($nextPageToken);
        print_r($deleteProducts);die;*/
        /*$userId = $this->di->getUser()->id;
        $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->deleteAllProductsFromMerchantCenter($userId);
        die('deleted all products without any error');*/


        /*$shop = 'bella-grace-jewelry-and-gifts.myshopify.com';
        $userId = $this->di->getUser()->id;
        $productsAlreadyFetched = [
            'container_count' => 0,
            'variant_count' => 0
        ];*/
        $shop = 'ced-jet.myshopify.com';
        $userId = $this->di->getUser()->id;
        $allProducts = $this->di->getObjectManager()->get('\App\Engine\Models\ProductFeeds')->getAllowedQuantityOfProducts($userId, $shop, 'google', 0);
        print_r($allProducts);
        die;
        /*$prodCount = $this->di->getObjectManager()->get('\App\Shopify\Components\Helper')->getProductsCount($userId, $shop);
        var_dump($prodCount);die;*/

        /*$data = [
                    'email' => 'satyaprakash@cedcoss.com',
                    'subject' => 'Testing',
                    'content' => 'testing',

                ];
       $data['path'] = $path;
            $data['link'] = $link;
            $data['subject'] = 'Password Reset Mail';

        $helper = $this->di->getObjectManager()->get('\App\Core\Components\SendMail');
        $helper->send($data);;

        die;*/
        $shop = 'bella-grace-jewelry-and-gifts.myshopify.com';
        $userId = $this->di->getUser()->id;
        $webhooks = $this->di->getObjectManager()->get('\App\Connector\Components\Webhook')->getExistingWebhooks($shop, $userId);
        print_r($webhooks);
        die;
        $shop = 'info@bellagracestore.com';
        $userId = $this->di->getUser()->id;
        $webhooks = $this->di->getObjectManager()->get('\App\Connector\Components\Webhook')->createWebhooks($shop, $userId);
        print_r($webhooks);
        die;
        die;
        // $payemnt = new \App\Payment\Models\Payment;
        // $payemnt->processPayment(10, 'GBP', 'paypal', []);
        // $da = $this->di->getCache()->get('config')->get('payment_methods');
        // var_dump($da);die;
        // $eventsManager = $this->di->getEventsManager();
        // $eventsManager->fire('application:createAfter', $this);
        // try{

        /*foreach($row){
            $i = 0;
            foreach($column){
                $column_length = $this->getNameFromNumber($i);
                $excel->getActiveSheet()->setCellValue($column_length.$r, $column_val);
                $i++;
            }
            $r++;
        }*/
        // } catch (\Exception $e) {
        //     echo $e->getMessage();die;
        // }
        $len = 60;
        for ($i = 0; $i <= 60; $i++) {
            echo $this->getNameFromNumber($i);
            echo '<br></br>';
        }
        die('test1');
        $connectorModelObj = \App\Core\Models\User::findFirst(24);
        var_dump($connectorModelObj->getToken());
        die("lll");
        $template = $compiler->getCompiledTemplatePath();
        extract(['check' => ['hello' => 'variable working']]);
        ob_clean();
        require $template;
        $content = ob_get_clean();
        print_r($content);
        die;
        $compiler = new VoltCompiler;
    }

    public function sendMailAction() {
        $getParams = $this->di->request->get();
        if (isset($getParams['email']) &&
            isset($getParams['template']) &&
            isset($getParams['subject'])) {
            $data = [
                'email' => $getParams['email'],
                'subject' => $getParams['subject'],
                'path' => 'core' . DS . 'view' . DS . 'email' . DS . $getParams['template'] . '.volt'
            ];
        }
    }

    public function getNameFromNumber($num)
    {
        $numeric = $num % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval($num / 26);
        if ($num2 > 0) {
            return $this->getNameFromNumber($num2 - 1) . $letter;
        } else {
            return $letter;
        }
    }

    public function reactTestAction()
    {
        /*$contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }*/
        $return = [
            [
                'Test1',
                'test1',
                123
            ],
            [
                'Test2',
                'test2',
                1235
            ],
            [
                'Test3',
                'test3',
                12366
            ]
        ];
        return $this->prepareResponse($return);
    }

}
