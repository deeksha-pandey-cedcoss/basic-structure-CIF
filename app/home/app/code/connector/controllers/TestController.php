<?php
namespace App\Connector\Controllers;

use Phalcon\Mvc\Controller;

// use Phalcon\Di;
// use App\Connector\Models\Profile\Helper;
// use App\Connector\Models\Profile\UploadHelper;
// use App\Connector\Models\Profile\ProductHelper;
// use App\Connector\Components\Dynamo;
// use App\Core\Models\BaseMongo;

class TestController extends Controller
{
    public function indexAction()
    {
      
        $productContainerTableAlias = 'pc';
        $productTableAlias = 'p';
        $productAttrTable = 'product_attribute';
        $productAttrTableAlias = 'pa';

        $prod_id = 10;

        $query = 'Select `pc`.*';
        $attType = [];
        $attId = [];
        $proAttr = new \App\Connector\Models\ProductAttribute;
        $containerAttr = $proAttr::find(["created_for='container' AND merchant_id='59'"]);
        foreach ($containerAttr as $attr) {
            $attType[] = $attr->getBackendType();
            $attId[] = $attr->getId();
            $query .= ', `pa_'.$attr->getBackendType().'`.`value` as `'.$attr->getCode().'`';
        }
        $attType = array_unique($attType);
        $attId = array_unique($attId);
        $query .= ' FROM `product_container` as `pc` ';
        foreach ($attType as $type) {
            $query .= "LEFT JOIN `product_attribute_".$type."` as `pa_".$type."` on `pa_".$type."`.`entity_id` = `pc`.`id` Where `pa_".$type."`.`type` = 'container' AND `pa_".$type."`.`attribute_id` IN  (".implode(',', $attId).") ";
        }

        echo $query;
        die;
        foreach ($filterParams as $key => $value) {
            $query = "SELECT id FROM `" . $productAttrTable . "` WHERE `code`='" . $key . "'";
            $attribute = $sqlConfig->sqlRecords($query, "one");
            if (isset($attribute['id'])) {
                $isAttributeExist = true;
                $table = $productAttrTable . "_" . $attribute['backend_type'];
                $joinQuery[] = " LEFT JOIN `" . $table . "` `" . $table . "` ON `" . $productContainerTableAlias . "`.`id`=`" . $table . "`.`entity_id` OR `" . $productTableAlias . "`.`id`=`" . $table . "`.`entity_id`";
                $onCondition[] = "`" . $productAttrTableAlias . "`.`id`=`" . $table . "`.`attribute_id`";
                $conditions[] = "`code`='" . $key . "'";
            }
        }
        echo '<h1>Hello1121!</h1>';
    }

    public function testAction()
    {
        //   $fvp = fopen(BP . DS . 'var' . DS . 'upload-report' . DS . 'bing' . DS . 'csv'. DS . 'test' . '.csv', 'w');
        //   fputcsv($fvp, ['SNo.', 'Report']);
        //   fclose($fvp);
        //   echo 'done';
        $product = [
          "count" => 10,
          "from" => [
            "shop_id" => "7"
          ],
          "to" => [
            "shop_id" => "2"
          ],
          "productOnly" => "true",
          "source_product_id" => [
            4597646655625
          ],
          "user_id" => "61e9058c11bd1940736da7a2"
       ];
      $objectManager = $this->di->getObjectManager();
      $helper = $objectManager->get('\App\Connector\Models\Product\Index');
      $res = $helper->getProducts($product);
      print_r($res);
      die();
        $product = [
            "sku" => "140439",
            "source_product_id" => "140439",
            "target_product_id" => "4546546",
            'any other details' => ' you want to add in container DB',
            "status" =>  [
               'status' => "error",
               'any other details' => ' you want to add in container DB'
            ],
         ];
        $objectManager = $this->di->getObjectManager();
        $helper = $objectManager->get('\App\Connector\Models\MarketplaceProduct');
        $res = $helper->createMarketplaceProduct([$product], 'mercado_cbt', 23, 4, "608920e62c89040c1c543822");
        print_r($res);
        die();
//        // Resolve the service (NOTE: $myClass->setDi($di) is automatically called)
//
//        var_dump($this->di->getConfig());
//        die;
//        $config = $objectManager->create('\App\Core\Models\Config');
//        var_dump($config->get('checking'));
//        //$objectManager->get('\MyInterface')->createRoute();
//        return 'test123';
    }

    public function test1Action()
    {
        $data='{"services":[

            {
                "title":"Amazon Importer",
                "code":"amazon_importer",
                "type":"importer",
                "charge_type":"Prepaid",
                "required":0,
                "service_charge":"20",
                "prepaid":{
                    "service_credits":"20",
                    "validity_changes":"Replace",
                    "fixed_price":20,
                    "reset_credit_after":20,
                    "expiring_at":"20"
                },
                "postpaid":{
                    "per_unit_usage_price":"",
                    "capped_amount":""
                }
            },
            {
                "title":"Ebay Importer",
                "code":"ebay_importer",
                "type":"importer",
                "charge_type":"Prepaid",
                "required":"yes",
                "service_charge":"20",
                "prepaid":{
                    "service_credits":"20",
                    "validity_changes":"Replace",
                    "fixed_price":20,
                    "reset_credit_after":20,
                    "expiring_at":"20"
                },
                "postpaid":{
                    "per_unit_usage_price":"",
                    "capped_amount":""
                }
            }
        
        ]}';
        $data=json_decode($data);
        // Resolve the service (NOTE: $myClass->setDi($di) is automatically called)
        // $objectManager = $this->di->getObjectManager();
        $product = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->initiateSync($userId=69);
        //$objectManager->get('\MyInterface')->createRoute();
        return 'test1WAESDR3';
    }


    public function testprofileAction()
    {
        $data = '{
            "name":"Clothing",
            "query":"(type %LIKE% variation)",
            "category_id":"6030bfefe728c462780f2da2",
            "profile_id":"601a7910785aac6e81021bb2",
            "data":{
               "inventory_settings":{
                  "data_type":"template",
                  "id":"6017ff27940d874f856de7c6"
               }
            },
            "attributes_mapping":{
               "name":{
                  "type":"attribute",
                  "value":"title1"
               }
            },
            "targets":{
               "ebay":{
                  "skip_query":"(product_type %LIKE% OUTDOOR_LIVING)",
                  "data":{
                     "inventory_settings":{
                        "threshhold":44
                     }
                  },
                  "shops":{
                     "123":{
                        "shop_id":"123",
                        "warehouses":{
                           "321":{
                              "warehouse_id":"321",
                              "data":{
                                 "inventory_settings":{
                                    "threshhold":44
                                 }
                              },
                              "sources":{
                                 "587":{
                                    "data":{
                                       "inventory_settings":{
                                          "threshhold":42
                                       }
                                    },
                                    "shops":{
                                       "158":{
                                          "shop_id":"158",
                                          "warehouses":{
                                             "546":{
                                                "warehouse_id":"546",
                                                "data":{
                                                   "inventory_settings":{
                                                      "threshhold":47
                                                   }
                                                },
                                                            "attributes_mapping":{
                                                      "name":{
                                                         "type":"attribute",
                                                         "value":"title"
                                                      },
                                                      "brand":{
                                                         "type":"fixed",
                                                         "value":"levis"
                                                      },
                                                      "color":{
                                                         "type":"attribute_and_value",
                                                         "value":"colour",
                                                         "value_mapping":{
                                                            "Dark Red":"Red",
                                                            "Sky Blue":"Blue"
                                                         }
                                                      },
                                                      "clothingMaterial":{
                                                         "type":"rule",
                                                         "value":"applied rules"
                                                      }
                                                   }
                                             }
                                          }
                                       }
                                    }
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
         }';

        $data = json_decode($data, true);

        $test = [];

        $test['profile_data'] = $data;
        $test['marketplace'] = 'ebay';
        $test['source_marketplace'] = 'shopify_vendor';

        $profileHelperObj = new Helper();
        $profileHelperObj->process_data = $test;
        // $profileHelperObj->source_marketplace = "shopify_vendor1";
        $response = $profileHelperObj->getMappedProduct();

        print_r($response);
        die;
    }



  public function checkUploadingAction()
  {
  //  die("chhc");
      $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
          ->init('shopify', 'true')
          ->call('/order',['Response-Modifier'=>'0'],['id'=>'4030333550768','shop_id'=>'1139','fields'=>'id,name','status'=>'any'], 'GET');

          var_dump($remoteResponse);

    die("cjjc");
    $baseObj = new BaseMongo();
        $userCollections = $baseObj->getCollectionForTable('user_details');
        $allUser = $baseObj->findByField([]);
        $user_details = $this->di->getObjectManager()->get('\App\Core\Models\User\Details');
        $target = 'shopify';
        $marketplace = $target;
        $appCode = "amazon_sales_channel";
        $installedUser = [];

        foreach ($allUser as $key => $value) {
          if(!empty($value['shops']) && isset($value['username']) && (strpos($value['username'], 'myshopify') !== false)){
            $user_details_shopify = $user_details->getDataByUserID($value['user_id'], $target);
            
                $shopResponse = $this->di->getObjectManager()->get('\App\Connector\Components\ApiClient')->init($marketplace, true,$appCode)->call('/shop', [], [
                    'shop_id' => $user_details_shopify['remote_shop_id'],
                    'app_code'=> $appCode
                ]);
                if($shopResponse['success']){
                  if(is_null($shopResponse['data'])){
                      $installedUser[] = ['name'=>$shopResponse['data']['name'],'email'=>$shopResponse['data']['email']];

                  }
                } else {
                 

                }


          }
          
        }

        var_dump(json_encode($installedUser));

        die("vchvhhv");

  

     
      $inventoryComponent = $this->di->getObjectManager()->get('\App\Amazon\Components\Product\InventoryWebhook');
      $userId = '612259c05926ff021922baa8';
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $productContainer = $mongo->getCollectionForTable('product_container');
      $inventoryItemId = '1230';

     //  $product = $productContainer->findOne(['match'=>['inventory_item_id'=>$inventoryItemId,'user_id'=>$this->di->getUser()->id,'marketplace.amazon.status'=>['$in'=>['Active','Inactive','Incomplete','Uploaded']]]]);
   /*   print_r(json_encode(['$match'=>['inventory_item_id'=>$inventoryItemId,'user_id'=>$userId,'marketplace.amazon.status'=>['$in'=>['Active','Inactive','Incomplete','Uploaded']]]]));die;

      die("cjj");*/

       $product = $productContainer->aggregate([['$match'=>['inventory_item_id'=>$inventoryItemId,'user_id'=>$userId,'marketplace.amazon.status'=>['$in'=>['Active','Inactive','Incomplete','Uploaded']]]]],['typeMap'=>['root'=> 'array', 'document' => 'array']]);
       $product = $product->toArray();

       var_dump($product);
       die;

      $query = [];
      $query[] = ['$match'=>['user_id' =>$userId,'marketplace.amazon.status'=>['$exists'=>true],'quantity'=>['$exists'=>true]]];
      $query[] = ['$match'=>['marketplace.amazon.status'=>'Active']];

     // $query[] = ['$match'=>['user_id' =>$userId]];
      $productData = $productCollection->aggregate($query, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);
      $productData = $productData->toArray();

      $chunkData = array_chunk($productData, 25);
      echo "start process";
      echo PHP_EOL;
      foreach ($chunkData as $key => $product) {
        echo "inloop process";
        echo PHP_EOL;
        echo $key;
        echo PHP_EOL;
        $sourceProductIds = [];

        foreach ($product as $key => $value) {
          echo PHP_EOL;
          $sourceProductIds['source_product_ids'][] = $value['source_product_id'];
        }

        $return = $inventoryComponent->init(['user_id' => $userId])->updateNew($sourceProductIds);

        echo "innerloop process";
        echo PHP_EOL;
        sleep(2);
      }
      die("chhv");

     /* die("cjjvv");

      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $userCollections = $mongo->getCollectionForTable('user_details');

      $userData = $userCollections->find([], ['typeMap'=>['root'=> 'array', 'document' => 'array']]);
      $userDatas =  $userData->toArray();
      $awsConfig = include_once(BP . DS . 'app' . DS . 'etc' . DS . 'aws.php');*/

       // var_dump($awsConfig);die;
/*
      foreach ($userDatas as $key => $userData) {
        if(!empty($userData['shops']) && isset($userData['shops'][1]))
        {
          $target = $this->di->getObjectManager()->get('\App\Shopifyhome\Components\Shop\Shop')->getUserMarkeplace();
          $userId = $userData['user_id'];

          $user_details = $this->di->getObjectManager()->get('\App\Core\Models\User\Details');
          $user_details = $user_details->getDataByUserID($userId, $target);
          if($user_details['remote_shop_id'] == '54')
          {
            $sqsConfig = [
              'region' => $awsConfig['region'],
              'key' => $awsConfig['credentials']['key'],
              'secret' => $awsConfig['credentials']['secret']
          ];
          
          $handlerData = [
              'type' => 'full_class',
              'class_name' => '\App\Shopifyhome\Components\Webhook\Route\Requestcontrol',
              'method' => 'registerWebhooks',
              'queue_name' => 'shopify_register_webhook',
              'user_id' => $userId,
              'data' => [
                  'user_id' => $userId,
                  'remote_shop_id' => $user_details['remote_shop_id'],
                  'sqs' => $sqsConfig,
                  'cursor' => 0,
                  'source' => 'amazon_sales_channel',
                  'target' => $target
              ]
          ];
          $rmqHelper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
          $queueNo = $rmqHelper->createQueue($handlerData['queue_name'], $handlerData);
          die("cjjc");

          }*/
          
          /*$sqsConfig = [
              'region' => $awsConfig['region'],
              'key' => $awsConfig['credentials']['key'],
              'secret' => $awsConfig['credentials']['secret']
          ];
          
          $handlerData = [
              'type' => 'full_class',
              'class_name' => '\App\Shopifyhome\Components\Webhook\Route\Requestcontrol',
              'method' => 'registerWebhooks',
              'queue_name' => 'shopify_register_webhook',
              'user_id' => $userId,
              'data' => [
                  'user_id' => $userId,
                  'remote_shop_id' => $user_details['remote_shop_id'],
                  'sqs' => $sqsConfig,
                  'cursor' => 0,
                  'source' => 'amazon_sales_channel',
                  'target' => $target
              ]
          ];
          $rmqHelper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
          $queueNo = $rmqHelper->createQueue($handlerData['queue_name'], $handlerData);*/

         // var_dump($queueNo,$handlerData);die;


 /*       }
      }


      die("cvjvj");*/
      
     /* $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
              ->init('shopify', 'true')
              ->call('/order',['Response-Modifier'=>'0'],['shop_id'=>'178','fields'=>'id,name','status'=>'any'], 'GET');
              var_dump($remoteResponse);die;*/



      $orderJson = '/var/www/invalidOrderWithAmazonOrderId.json';
      $orderDatas = json_decode(file_get_contents($orderJson),true);
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $userCollections = $mongo->getCollectionForTable('user_details');

      $orderFile = '/var/www/order.json';

      $satyaSirdatas = json_decode(file_get_contents($orderFile),true);

  /*    $page = $this->request->get()['page'];
      $limit = 2;
      $lower = $limit * (int)$page;
      $uper =  $lower+$limit;
      $counter = 0;*/
   
      $needToWork = [];
      foreach ($satyaSirdatas as $key => $satyaSirdata) {
        if(isset($orderDatas[$satyaSirdata['order_id']]))
        {
          $customers = $satyaSirdata['customers'];
          $userId = $orderDatas[$satyaSirdata['order_id']]['user_id'];
          unset($customers[$userId]);

          $needToWork[$orderDatas[$satyaSirdata['order_id']]['shopifyId']] = $customers;

        }
      }
      $remoteShopIds = [];
      $deleteUserFound = [];
      
      //var_dump($needToWork);die;


      foreach ($needToWork as $orderId => $customers) 
      {
/*        if($lower>$counter)
        {
          $counter++;
          continue;
        }
        if($uper<$counter)
        {
          $counter++;
          continue;
        }*/
        foreach ($customers as $customer => $attempt) {
          $remote_shop_id = 0;

          if(!isset($remoteShopIds[$customer]))
          {
            $userData = $userCollections->findOne(['user_id'=>$customer], ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

            if($userData)
            {

              $remoteShopIds[$customer] = $userData['shops'][0]['remote_shop_id'];
              $remote_shop_id = $remoteShopIds[$customer];
            }
          } else {
            $remote_shop_id = $remoteShopIds[$customer];
          }

          $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
          ->init('shopify', 'true')
          ->call('/order',['Response-Modifier'=>'0'],['id'=>$orderId,'shop_id'=>$remote_shop_id,'fields'=>'id,name','status'=>'any'], 'DELETE');

          var_dump($remoteResponse);

          if($remoteResponse && $remoteResponse['success'])
          {
            $deleteUserFound[$customer][] = $orderId;
          }
          sleep(2);
        }

        die;
      }
      var_dump($deleteUserFound);die;

      die("out");




/*      $orderJson = '/var/www/invalidOrderWithAmazonOrderId.json';
      $orderDatas = json_decode(file_get_contents($orderJson),true);
      $remoteShopIds = [];
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');

      $userCollections = $mongo->getCollectionForTable('user_details');
      $collection = $mongo->getCollectionForTable('order_container');
      $userWiseData = [];

       foreach ($orderDatas as $amazonOrderId => $orderData) {
          $userWiseData[$orderData['user_id']][] = $orderData;
       }
       
       foreach ($userWiseData as $userId => $allData) {
          if(!isset($remoteShopIds[$userId]))
          {
            $userData = $userCollections->findOne(['user_id'=>$userId], ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

            if($userData)
            {
              $userWiseData[$userId]['shop'] = $userData['username'];

              $remoteShopIds[$userId] = $userData['shops'][0]['remote_shop_id'];
              $remote_shop_id = $remoteShopIds[$userId];
            }
          } else {
            $remote_shop_id = $remoteShopIds[$userId];
          }
          $orderIds = [];

          foreach ($allData as $key => $singleData) {
            $orderIds[] = $singleData['shopifyId'];
            $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
              ->init('shopify', 'true')
              ->call('/order',['Response-Modifier'=>'0'],['id'=>$singleData['shopifyId'],'shop_id'=>$remote_shop_id,'fields'=>'id,name','status'=>'any'], 'GET');
            if(!$remoteResponse['success'])
            {
              $unset = ['$unset'=>['target_status'=>1,
                  'target_order_id'=>1,
                  'shopify_order_name'=>1,
                  'imported_at'=>1,
                  'target_error_message'=>1,
                  'target_errors'=>1,
                  'target_order_data'=>1]];
              $res = $collection->updateOne(['source_order_id'=>$singleData['amazon_order_id']],$unset);


            }
            
          }
       }*/

       var_dump(json_encode($userWiseData));die;


       var_dump($userWiseData);die;


  /*    foreach ($orderDatas as $amazonOrderId => $orderData) {
        $shopifyOrderId = $orderData['shopifyId'];
        $userId = $orderData['user_id'];
        $remote_shop_id = 0;
        if(!isset($remoteShopIds[$userId]))
        {
          $userData = $userCollections->findOne(['user_id'=>$userId], ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

          if($userData)
          {
            $remoteShopIds[$userId] = $userData['shops'][0]['remote_shop_id'];
            $remote_shop_id = $remoteShopIds[$userId];
          }
        } else {
          $remote_shop_id = $remoteShopIds[$userId];
        }
      
        $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
          ->init('shopify', 'true')
          ->call('/order',['Response-Modifier'=>'0'],['id'=>$shopifyOrderId,'shop_id'=>$remote_shop_id,'sales_channel'=>0,'fields'=>'id,name'], 'GET');


        if($remoteResponse && $remoteResponse['success'] && !empty($remoteResponse['data']))
        {
          if(isset($remoteResponse['data']['id']))
          {

          } else {
            $collection->deleteOne(['source_order_id'=>$amazonOrderId]);
          }

        } else {
          if(!$remoteResponse['success'])
          {
            $collection->deleteOne(['source_order_id'=>$amazonOrderId]);
          }
        }
      }
      die("cjjcj");*/

  /*    $orderJson = '/var/www/invalidOrders.json';
      $orderDatas = json_decode(file_get_contents($orderJson),true);
      $orderDatas = array_values($orderDatas);

      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $collection = $mongo->getCollectionForTable('order_container');

      $orderChunk = array_chunk($orderDatas, 100);
      $dataWithAmazonOrderId = [];

      foreach ($orderChunk as $key => $targetOrderIds) 
      {
        $query = [];
        $query[] = ['$match'=>['target_order_id' =>['$in'=>$targetOrderIds]]];
        $getOrders = $collection->aggregate($query, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);
        if(!empty($getOrders))
        {
          $getOrders = $getOrders->toArray();
          foreach ($getOrders as $key => $getOrder) {
            $dataWithAmazonOrderId[$getOrder['source_order_id']] = ['amazon_order_id'=>$getOrder['source_order_id'],'shopifyId'=>$getOrder['target_order_id'],'user_id'=>$getOrder['user_id'],'imported_at'=>$getOrder['imported_at']];
          }
        }
      }*/

      print_r(json_encode($dataWithAmazonOrderId));die;

      


        /*$orderJson = '/var/www/orders-shopify.json';
        $orderDatas = json_decode(file_get_contents($orderJson),true);
        $needToWork = [];
        $fetchedData = $orderDatas;
        $neetNotWork = [];
        $allShopifyIds = [];

        foreach ($orderDatas as $remote_shop_id => $orderData) {
          $orderchunk = array_chunk($orderData, 50);
          

          foreach ($orderchunk as $key => $allIds) {
            $orderIds = implode(',', $allIds);
            $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
            ->init('shopify', 'true')
            ->call('/order',['Response-Modifier'=>'0'],['ids'=>$orderIds,'shop_id'=>$remote_shop_id,'fields'=>'id,name','status'=>'any'], 'GET');
            
            if($remoteResponse && $remoteResponse['success'] && $remoteResponse['data'] && !empty($remoteResponse['data']['orders']))
            {
              foreach ($remoteResponse['data']['orders'] as $key => $shopifyOrder) {
                  if(in_array($shopifyOrder['id'], $fetchedData[$remote_shop_id]))
                  {
                    $neetNotWork[$remote_shop_id][] = $shopifyOrder['id'];
                    
                  } else 
                  {
                    $needToWork[$remote_shop_id][] = $shopifyOrder['id'];
                    $allShopifyIds[$shopifyOrder['id']] = $shopifyOrder['id'];
                  }
              }

            } else {
              if($remoteResponse['success'])
              {
               foreach ($allIds as $key => $id) {
                 $allShopifyIds[$id] = $id;
               }
                $needToWork[$remote_shop_id][] = $allIds;
              }

            }

          }*/

      /*    $orderIds = implode(',', $orderData);
          

          $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
          ->init('shopify', 'true')
          ->call('/order',['Response-Modifier'=>'0'],['ids'=>$orderIds,'shop_id'=>$remote_shop_id,'sales_channel'=>0,'fields'=>'id,name'], 'GET');


          if($remoteResponse && $remoteResponse['success'] && $remoteResponse['data'] && !empty($remoteResponse['data']['orders']))
          {
            foreach ($remoteResponse['data']['orders'] as $key => $shopifyOrder) {
                if(in_array($shopifyOrder['id'], $fetchedData[$remote_shop_id]))
                {
                  $neetNotWork[$remote_shop_id][] = $shopifyOrder['id'];
                  
                } else 
                {
                  $needToWork[$remote_shop_id][] = $shopifyOrder['id'];
                }
            }

          } else {
            $needToWork[$remote_shop_id][] = $orderData;
          }*/

       /* }
        var_dump(count($allShopifyIds),json_encode($allShopifyIds));
        die("jjvjvj");*/


  /*    $orderJson = '/var/www/order.json';
      $orderData = json_decode(file_get_contents($orderJson),true);
      
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $userCollections = $mongo->getCollectionForTable('user_details');
      $collection = $mongo->getCollectionForTable('order_container');
      $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
      $count = 1;
      $ncout = 1;
      $notFoundOrder = [];
      $commonHelper = $this->di->getObjectManager()->get('App\Amazon\Components\Common\Helper');
      $page = $this->request->get()['page'];
      $limit = 1000;
      $lower = $limit * (int)$page;
      $uper =  $lower+$limit;
      $orderNeedToWork = [];
 
      $orderIds = [];
      $userIds = [];

      foreach ($orderData as $key => $value) {
        if($lower>$key)
        {
          continue;
        }
        if($uper<$key)
        {
          continue;
        }
        $orderIds[] = $value['order_id'];
      }
      $query[] = ['$match'=>['source_order_id' =>['$in'=>$orderIds]]];
      $getOrders = $collection->aggregate($query, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

      if(!empty($getOrders))
      {
        $getOrders = $getOrders->toArray();
        foreach ($getOrders as $key => $getOrder) {
          $userIds[] = $getOrder['user_id'];
        }


        $userQuery[] = ['$match'=>['user_id' =>['$in'=>$userIds]]];
        $allUsers = $userCollections->aggregate($userQuery, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

        if(!empty($allUsers))
        {
          $allUsers = $allUsers->toArray();
          $userWiseData = [];
          foreach ($allUsers as $key => $value) {
            $userWiseData[$value['user_id']] = $value;
          }
        }

        foreach ($getOrders as $key => $getOrder) {
          $remoteShopId = 0;
          $allUsers[] = $getOrder['user_id'];

          if(isset($getOrder['target_order_id']))
          {
            
            $userData = $userCollections->findOne(['user_id'=>$getOrder['user_id']],$options);
            if(isset($userWiseData,$userWiseData[$getOrder['user_id']]))
            {
              $shops = $userWiseData[$getOrder['user_id']]['shops'];
                foreach ($shops as $key => $userVal) {
                    if(!$key)
                    {
                      $remoteShopId = $userVal['remote_shop_id'];
                    } 
                  if($userVal['_id'] == $getOrder['shop_id'])
                  {
                      
                  }
                }
            }
           
            if($remoteShopId)
            {
              $params = [
              'shop_id' => $remoteShopId,
              'amazon_order_id' => $getOrder['source_order_id'],
              'home_shop_id' => $getOrder['shop_id']
              ];
              $orderNeedToWork[$remoteShopId][] = $getOrder['target_order_id'];

            } else {
              die("jjvjv");

              $orderNeedToWork[] = $getOrder['source_order_id'];
            }

          } 
        }

      }
      var_dump(count($orderNeedToWork),json_encode($orderNeedToWork));*/


      /*$content = file_get_contents('/var/www/orders-make.json');

      $getDatas = json_decode($content,true);*/


/*      $dynamoData = [];
      $counter = 1000;

      foreach ($getDatas as $remote_shop_id => $getData) {
        foreach ($getData as $key => $value) {
          $counter++;
          $dynamoData[] = [
            'amazon_order_id'=>$value,
            'remote_shop_id'=> (string)$remote_shop_id,
            'id'=>(string) $counter,
            'iserror'=>'3'
          ];
        }
      }
      $dynamoData = array_chunk($dynamoData, 24);
      foreach ($dynamoData as $key => $data) {
       // var_dump($data);die;

        $dynamoObj = $this->di->getObjectManager()->get(Dynamo::class);
        $dynamoObj->setTable('order_dublicate');
        $dynamoObj->setUniqueKeys(['id']);
        $dynamoObj->setTableUniqueColumn('id');
        $res = $dynamoObj->save($data);
      }*/

      die("cjjcj");
      /*filter from amazon*/

/*      $orderJson = '/var/www/order.json';
      $orderData = json_decode(file_get_contents($orderJson),true);
      
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $userCollections = $mongo->getCollectionForTable('user_details');
      $collection = $mongo->getCollectionForTable('order_container');
      $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
      $count = 1;
      $ncout = 1;
      $notFoundOrder = [];
      $commonHelper = $this->di->getObjectManager()->get('App\Amazon\Components\Common\Helper');
      $page = $this->request->get()['page'];
      $limit = 1000;
      $lower = $limit * (int)$page;
      $uper =  $lower+$limit;
      $orderNeedToWork = [];
     // var_dump($lower,$uper);die;
      $orderIds = [];
      $userIds = [];

      foreach ($orderData as $key => $value) {
        if($lower>$key)
        {
          continue;
        }
        if($uper<$key)
        {
          continue;
        }
        $orderIds[] = $value['order_id'];
      }
      $query[] = ['$match'=>['source_order_id' =>['$in'=>$orderIds]]];
      $getOrders = $collection->aggregate($query, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

      if(!empty($getOrders))
      {
        $getOrders = $getOrders->toArray();
        foreach ($getOrders as $key => $getOrder) {
          $userIds[] = $getOrder['user_id'];
        }


        $userQuery[] = ['$match'=>['user_id' =>['$in'=>$userIds]]];
        $allUsers = $userCollections->aggregate($userQuery, ['typeMap'=>['root'=> 'array', 'document' => 'array']]);

        if(!empty($allUsers))
        {
          $allUsers = $allUsers->toArray();
          $userWiseData = [];
          foreach ($allUsers as $key => $value) {
            $userWiseData[$value['user_id']] = $value;
          }
        }

        foreach ($getOrders as $key => $getOrder) {
          $remoteShopId = 0;
          $allUsers[] = $getOrder['user_id'];

          if(isset($getOrder['target_order_id']))
          {
            
            $userData = $userCollections->findOne(['user_id'=>$getOrder['user_id']],$options);
            if(isset($userWiseData,$userWiseData[$getOrder['user_id']]))
            {
              $shops = $userWiseData[$getOrder['user_id']]['shops'];
                foreach ($shops as $key => $userVal) {
                    if(!$key)
                    {
                      continue;
                    } 
                  if($userVal['_id'] == $getOrder['shop_id'])
                  {
                      $remoteShopId = $userVal['remote_shop_id'];
                  }
                }
            }
           
            if($remoteShopId)
            {
              $params = [
              'shop_id' => $remoteShopId,
              'amazon_order_id' => $getOrder['source_order_id'],
              'home_shop_id' => $getOrder['shop_id']
              ];
              $orderNeedToWork[$remoteShopId][] = $getOrder['source_order_id'];

            } else {
              die("jjvjv");

              $orderNeedToWork[] = $getOrder['source_order_id'];
            }

          } 
        }

      }
      var_dump(count($orderNeedToWork),json_encode($orderNeedToWork));*/
      /*filter from amazon done*/

      die("done");
     
      $params = [
        'imported_at'=>['$gte'=>'2021-08-05T01:54:33-04:00']
      ];
      $orderData = $collection->find($params,['projection'=>['_id'=>0,'source_order_id'=>1]])->toArray();
      
      $logFile = "amazon/test/".date('d-m-Y').'.log';

            $this->di->getLog()->logContent('orderData : '.json_encode($orderData), 'info', $logFile);
      die;


      die("need to remove die");
        $baseObj = new BaseMongo();
        $userCollections = $baseObj->getCollectionForTable('user_details');
        $allUser = $baseObj->findByField([]);
        $user_details = $this->di->getObjectManager()->get('\App\Core\Models\User\Details');
        $target = 'shopify';
        $marketplace = $target;
        $appCode = "amazon_sales_channel";

        foreach ($allUser as $key => $value) {
          if(!empty($value['shops']) && isset($value['username']) && (strpos($value['username'], 'myshopify') !== false)){
            $user_details_shopify = $user_details->getDataByUserID($value['user_id'], $target);
            
                $shopResponse = $this->di->getObjectManager()->get('\App\Connector\Components\ApiClient')->init($marketplace, true,$appCode)->call('/shop', [], [
                    'shop_id' => $user_details_shopify['remote_shop_id'],
                    'app_code'=> $appCode
                ]);
                if($shopResponse['success']){
                  if(is_null($shopResponse['data'])){
                    print_r($value['username']);

                    $user_id = $value['user_id'];

                    $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
                    $collection = $mongo->getCollectionForTable('uninstalled_user');
                    $exists = $mongo->loadByField([
                      "user_id"=> $user_id,
                    ]);

                    if($exists)
                    {
                      //$collection->updateOne(['user_id'=>$user_id],[]);
                    } else {
                       $collection->insertOne($value);
                    }


                     $collection = $mongo->getCollectionForTable('product_container');
                     $collection->deleteMany(['user_id' => (string)$user_id]);

                     $collection = $mongo->getCollectionForTable('amazon_product_container');
                     $collection->deleteMany(['user_id' => (string)$user_id]);

                     
                     $collection = $mongo->getCollectionForTable('configuration');
                     $collection->deleteMany(['user_id' => (string)$user_id]);

                     $collection = $mongo->getCollectionForTable('profiles');
                     $collection->deleteMany(['user_id' => (string)$user_id]);

                     $collection = $mongo->getCollectionForTable('profile_settings');
                     $collection->deleteMany(['merchant_id' => (string)$user_id]);



                     $collection = $mongo->getCollectionForTable('amazon_listing');
                     $collection->deleteMany(['user_id' => (string)$user_id]);


                     $collection = $mongo->getCollectionForTable('user_details');
                     $collection->deleteOne(['user_id' => (string)$user_id]);

                      $responseWbhook = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                                ->init($target, 'true')
                                ->call("/webhook/unregister", [], ['shop_id' => $user_details_shopify['remote_shop_id']], 'DELETE');
                      print_r($responseWbhook);
                      echo "<br>";
                     $target = $this->di->getObjectManager()->get('\App\Shopifyhome\Components\Shop\Shop')->getUserMarkeplace();
                     $shopifyUninstall = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                         ->init($target, 'true')
                         ->call('app-shop',[],['shop_id'=> $user_details_shopify['remote_shop_id']], 'DELETE');

                      print_r($shopifyUninstall);
                      echo "<br>";

                  }
                } else {
                  print_r($shopResponse);die;

                }


          }
          
        }

        die("vchvhhv");


      $jsonData = '[ { "Date": "2021-06-24 17:21:33 UTC", "Shop domain": "the-village-country-store.myshopify.com" }, { "Date": "2021-06-24 19:52:16 UTC", "Shop domain": "the-southern-magnolia-too.myshopify.com" }, { "Date": "2021-06-24 20:22:04 UTC", "Shop domain": "ticoroasters.myshopify.com" }, { "Date": "2021-06-24 22:12:27 UTC", "Shop domain": "oh-boy-records.myshopify.com" }, { "Date": "2021-06-25 01:16:43 UTC", "Shop domain": "empiremotor.myshopify.com" }, { "Date": "2021-06-25 01:48:13 UTC", "Shop domain": "nomad-discoveries.myshopify.com" }, { "Date": "2021-06-25 13:15:51 UTC", "Shop domain": "unminced-words.myshopify.com" }, { "Date": "2021-06-25 16:23:14 UTC", "Shop domain": "ticoroasters.myshopify.com" }, { "Date": "2021-06-27 03:49:52 UTC", "Shop domain": "marlyn-boutique-llc.myshopify.com" }, { "Date": "2021-06-27 18:21:19 UTC", "Shop domain": "ms-ms-plus-size-shop.myshopify.com" }, { "Date": "2021-06-28 04:11:43 UTC", "Shop domain": "sarahlewisjewelrycom.myshopify.com" }, { "Date": "2021-06-28 13:30:59 UTC", "Shop domain": "shoxtec-suspension.myshopify.com" }, { "Date": "2021-06-29 08:26:04 UTC", "Shop domain": "sockball.myshopify.com" }, { "Date": "2021-06-29 15:59:36 UTC", "Shop domain": "muratis-studios.myshopify.com" }, { "Date": "2021-06-29 18:22:27 UTC", "Shop domain": "ms-ms-plus-size-shop.myshopify.com" }, { "Date": "2021-06-29 21:11:15 UTC", "Shop domain": "love-to-hold-gift-store.myshopify.com" }, { "Date": "2021-06-30 06:00:02 UTC", "Shop domain": "sumairabass.myshopify.com" }, { "Date": "2021-07-04 20:35:30 UTC", "Shop domain": "dragons-den-collectibles.myshopify.com" }, { "Date": "2021-07-04 21:03:50 UTC", "Shop domain": "pivag-multi-trade.myshopify.com" }, { "Date": "2021-07-04 21:26:14 UTC", "Shop domain": "dragons-den-collectibles.myshopify.com" }, { "Date": "2021-07-05 15:35:27 UTC", "Shop domain": "bunker27.myshopify.com" }, { "Date": "2021-07-06 20:55:13 UTC", "Shop domain": "truff-hot-sauce.myshopify.com" }, { "Date": "2021-07-07 19:59:41 UTC", "Shop domain": "the-shoppe-depot.myshopify.com" }, { "Date": "2021-07-07 23:28:30 UTC", "Shop domain": "marlyn-boutique-llc.myshopify.com" }, { "Date": "2021-07-08 01:16:12 UTC", "Shop domain": "the-shoppe-depot.myshopify.com" }, { "Date": "2021-07-08 13:58:56 UTC", "Shop domain": "joepauls.myshopify.com" }, { "Date": "2021-07-08 16:39:44 UTC", "Shop domain": "shirtnado.myshopify.com" }, { "Date": "2021-07-08 17:03:11 UTC", "Shop domain": "stoneweardesigns.myshopify.com" }, { "Date": "2021-07-08 18:40:40 UTC", "Shop domain": "mikescooltest.myshopify.com" }, { "Date": "2021-07-08 19:37:45 UTC", "Shop domain": "cardboard-memories-inc.myshopify.com" }, { "Date": "2021-07-08 20:03:29 UTC", "Shop domain": "cellcynergy-nutrition.myshopify.com" }, { "Date": "2021-07-09 10:18:38 UTC", "Shop domain": "crossnet-game.myshopify.com" }, { "Date": "2021-07-09 17:42:15 UTC", "Shop domain": "prep-obsessed.myshopify.com" }, { "Date": "2021-07-09 21:04:18 UTC", "Shop domain": "lubelifeca.myshopify.com" }, { "Date": "2021-07-10 14:48:42 UTC", "Shop domain": "jickles-and-co.myshopify.com" }, { "Date": "2021-07-10 17:47:22 UTC", "Shop domain": "florida-state-line.myshopify.com" }, { "Date": "2021-07-11 13:09:52 UTC", "Shop domain": "shrunken-head.myshopify.com" }, { "Date": "2021-07-11 19:53:57 UTC", "Shop domain": "black-alpha-supplements-usa.myshopify.com" }, { "Date": "2021-07-11 21:19:25 UTC", "Shop domain": "thebossybrowshop.myshopify.com" }, { "Date": "2021-07-11 21:51:40 UTC", "Shop domain": "thebossybrowshop.myshopify.com" }, { "Date": "2021-07-12 10:35:10 UTC", "Shop domain": "onsalebeauty.myshopify.com" }, { "Date": "2021-07-12 15:48:18 UTC", "Shop domain": "mythicplasmaart.myshopify.com" }, { "Date": "2021-07-12 16:08:03 UTC", "Shop domain": "best-phone-band.myshopify.com" }, { "Date": "2021-07-12 16:14:40 UTC", "Shop domain": "umbrella-games.myshopify.com" }, { "Date": "2021-07-12 18:09:08 UTC", "Shop domain": "shop-ilusion.myshopify.com" }, { "Date": "2021-07-12 19:25:42 UTC", "Shop domain": "mintandlily.myshopify.com" }, { "Date": "2021-07-12 21:27:31 UTC", "Shop domain": "babyo-clothing-co.myshopify.com" }, { "Date": "2021-07-12 22:18:50 UTC", "Shop domain": "flavortoothpicks.myshopify.com" }, { "Date": "2021-07-12 23:03:58 UTC", "Shop domain": "juice-krate-shop.myshopify.com" }, { "Date": "2021-07-12 23:07:24 UTC", "Shop domain": "bamboo-little-shop.myshopify.com" }, { "Date": "2021-07-12 23:34:50 UTC", "Shop domain": "litt-ind.myshopify.com" }, { "Date": "2021-07-12 23:58:27 UTC", "Shop domain": "tkb-trading-llc.myshopify.com" }, { "Date": "2021-07-13 02:59:22 UTC", "Shop domain": "vent-works.myshopify.com" }, { "Date": "2021-07-13 03:04:04 UTC", "Shop domain": "madoorablecreations.myshopify.com" }, { "Date": "2021-07-13 12:14:43 UTC", "Shop domain": "gallery57wallart.myshopify.com" }, { "Date": "2021-07-13 14:17:57 UTC", "Shop domain": "new-york-puzzle-company.myshopify.com" }, { "Date": "2021-07-13 16:03:10 UTC", "Shop domain": "iaad.myshopify.com" }, { "Date": "2021-07-13 16:50:27 UTC", "Shop domain": "fat-kitty-designs.myshopify.com" }, { "Date": "2021-07-13 16:59:04 UTC", "Shop domain": "bohica-pepper-hut-2.myshopify.com" }, { "Date": "2021-07-13 17:00:37 UTC", "Shop domain": "kavu-hq.myshopify.com" }, { "Date": "2021-07-13 17:29:00 UTC", "Shop domain": "nashua-nutrition.myshopify.com" }, { "Date": "2021-07-13 17:50:52 UTC", "Shop domain": "anchoring-com.myshopify.com" }, { "Date": "2021-07-13 18:56:24 UTC", "Shop domain": "thapparel.myshopify.com" }, { "Date": "2021-07-13 23:10:36 UTC", "Shop domain": "refurbishedpro.myshopify.com" }, { "Date": "2021-07-14 07:32:47 UTC", "Shop domain": "bestforlessdrugtest.myshopify.com" }, { "Date": "2021-07-14 12:23:41 UTC", "Shop domain": "cedar-creek-rv-outdoor-center.myshopify.com" }, { "Date": "2021-07-14 14:43:40 UTC", "Shop domain": "skinperfectionnaturalandorganicskincare.myshopify.com" }, { "Date": "2021-07-14 16:45:05 UTC", "Shop domain": "hotleathers.myshopify.com" }, { "Date": "2021-07-14 17:44:30 UTC", "Shop domain": "loon-raccoon.myshopify.com" }, { "Date": "2021-07-14 18:00:14 UTC", "Shop domain": "carobou.myshopify.com" }, { "Date": "2021-07-15 01:48:32 UTC", "Shop domain": "your-auto-gear.myshopify.com" }, { "Date": "2021-07-15 15:14:35 UTC", "Shop domain": "dirty-hooker.myshopify.com" }, { "Date": "2021-07-15 18:47:28 UTC", "Shop domain": "modernboatsalesnadservice.myshopify.com" }, { "Date": "2021-07-15 20:26:34 UTC", "Shop domain": "destira-leotards.myshopify.com" }, { "Date": "2021-07-15 23:53:00 UTC", "Shop domain": "pack-simply.myshopify.com" }, { "Date": "2021-07-16 17:22:29 UTC", "Shop domain": "konnextixn.myshopify.com" }, { "Date": "2021-07-16 17:39:43 UTC", "Shop domain": "honeycat-jewelry.myshopify.com" }, { "Date": "2021-07-16 18:27:24 UTC", "Shop domain": "chez-shay-studio.myshopify.com" }, { "Date": "2021-07-17 05:03:40 UTC", "Shop domain": "tucannamerica.myshopify.com" }, { "Date": "2021-07-17 17:52:02 UTC", "Shop domain": "kriszbella.myshopify.com" }, { "Date": "2021-07-17 19:28:25 UTC", "Shop domain": "infinitec-store.myshopify.com" }, { "Date": "2021-07-19 01:34:54 UTC", "Shop domain": "ecotimelife.myshopify.com" }, { "Date": "2021-07-20 00:24:09 UTC", "Shop domain": "black-alpha-supplements-usa.myshopify.com" }, { "Date": "2021-07-20 11:33:02 UTC", "Shop domain": "giftsandmorebylinny.myshopify.com" }, { "Date": "2021-07-20 12:47:41 UTC", "Shop domain": "temey.myshopify.com" }, { "Date": "2021-07-20 15:15:50 UTC", "Shop domain": "bronxxotica.myshopify.com" }, { "Date": "2021-07-20 20:40:42 UTC", "Shop domain": "black-alpha-supplements-usa.myshopify.com" }, { "Date": "2021-07-21 18:50:25 UTC", "Shop domain": "lucky-ames-shop.myshopify.com" }, { "Date": "2021-07-21 23:38:18 UTC", "Shop domain": "srb-products-inc.myshopify.com" }, { "Date": "2021-07-22 00:22:11 UTC", "Shop domain": "bronxxotica.myshopify.com" } ]';

      $array = json_decode($jsonData,true);
      $baseObj = new BaseMongo();

      $userCollections = $baseObj->getCollectionForTable('user_details');
      foreach ($array as $key => $value) {
        $timeStamp = (new \DateTime($value['Date']))->getTimeStamp(); 
        $newDate = new \MongoDB\BSON\UTCDateTime($timeStamp*1000);
        $shopName = $value['Shop domain'];
        $userCollections->updateOne(['username'=>$shopName],['$push'=>['created_at'=>$newDate,'shops.0.created_at'=>$newDate,'shops.0.updated_at'=>$newDate,'shops.1.created_at'=>$newDate,'shops.1.updated_at'=>$newDate]]);
        print_r($shopName);die;

      }
      $timeStamp = (new \DateTime('2021-06-24 17:21:33 UTC'))->getTimeStamp(); 
      $newDate = new \MongoDB\BSON\UTCDateTime($timeStamp*1000);
      print_r($value);die;




      $dynamoObj = $this->di->getObjectManager()->get('App\Connector\Components\Dynamo');
      $dynamoObj->setTable('amazon_inventory_mgmt');

      $feedContent = [];
/*      $feedContent['123'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '10',
                'shop_id' => '8',
                'source_product_id'=>'123',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];*/
 /*      $feedContent['123'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '11',
                'shop_id' => '8',
                'source_product_id'=>'123',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];*/

/*       $feedContent['1234'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '10',
                'shop_id' => '8',
                'source_product_id'=>'1234',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];*/

    /*   $feedContent['1234'] = [
                'home_shop_id' =>'51',
                'marketplace_id' => '523',
                'shop_id' => '197',
                'source_product_id'=>'1234',
                'process'=>'1',
                'feedContent' => json_encode([
                  'Id' => 1234,
                  'SKU' => 'abcde',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];*/

      $feedContent['393011982500568'] = [
        'home_shop_id' =>'77',
        'marketplace_id' => 'A21TJRUUN4KGVARA',
        'shop_id' => '54',
        'source_product_id'=>'393011982500568',
        'process'=>'1',
        'feedContent' => json_encode([
        'Id' => 175580,
        'SKU' => '12374',
        'Quantity' =>100,
        'Latency' => 12
        ])
      ] ;

      $feedContent['393011982500569'] = [
        'home_shop_id' =>'77',
        'marketplace_id' => 'A21TJRUUN4KGVARA',
        'shop_id' => '54',
        'source_product_id'=>'393011982500569',
        'process'=>'1',
        'feedContent' => json_encode([
        'Id' => 175580,
        'SKU' => '12374',
        'Quantity' =>100,
        'Latency' => 12
        ])
      ] ;

      $dynamoObj->setTable('amazon_inventory_update');
      $dynamoObj->setUniqueKeys(['source_product_id','marketplace_id']);
      $dynamoObj->setTableUniqueColumn('id');
      
      $res = $dynamoObj->save($feedContent);
      print_r($res);die;

    


        $test = [];
        //$test['profile_id'] = '60b0c4fe7cd778765d6c43f3';
        $test['profile_id'] = '60b3a4abb6da67506a79bac3';

        $test['target_marketplace'] = 'ebay';
        $test['target_shop_id'] = '1';
        $test['warehouse_id'] = '1';
        $test['source_maketplace'] = 'shopify';
        $test['source_shop_id'] = '1';
        $test['source_shop_warehouse_id'] = '1';
        $test['app_tag'] = 'test2';

        $profileids = ["6619633778860","6619634696364"];
        $test['container_ids'] = $profileids;
        //$test['target_shop_id'] = 's11';

        $profileHelperObj = new ProductHelper();
        $profileHelperObj->process_data = $test;
        // $profileHelperObj->source_marketplace = "shopify_vendor1";
        //$response = $profileHelperObj->getProductByProfileId();

        $response = $profileHelperObj->getProductByProductIds();
        print_r($response);
        die;
    }

    public function savedynamoAction()
    {
      $dynamoObj = $this->di->getObjectManager()->get('App\Connector\Components\Dynamo');
      $dynamoObj->setTable('amazon_inventory_mgmt');

      $feedContent = [];
      $feedContent['123'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '10',
                'shop_id' => '8',
                'source_product_id'=>'123',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];
       $feedContent['123'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '11',
                'shop_id' => '8',
                'source_product_id'=>'123',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];

       $feedContent['1234'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '10',
                'shop_id' => '8',
                'source_product_id'=>'1234',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];

       $feedContent['1234'] = [
                'home_shop_id' =>'8',
                'marketplace_id' => '11',
                'shop_id' => '8',
                'source_product_id'=>'1234',
                'feedContent' => json_encode([
                  'Id' => 123,
                  'SKU' => 'abc',
                  'Quantity' =>1,
                  'Latency' => 1
                ])
      ];

      $dynamoObj->setTable('amazon_inventory_mgmt');
      $dynamoObj->setUniqueKeys(['source_product_id','marketplace_id']);
      $res = $dynamoObj->processData('amazon_inventory_mgmt');
      print_r($res);die;

    }
/*
    public function savedynamoAction()
    {

    }*/
}
