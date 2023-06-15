<?php

namespace App\Connector\Components;

use Phalcon\Events\Event;

class Testprofile extends \App\Core\Components\Base
{

    /**
     * @param Event $event
     * @param $myComponent
     */
    public function beforeSave(Event $event,$myComponent , $data)
    {

        $actualData = $event->getData();

        //$actualData['custom_data']['name'] = "change";
        try {

        } catch (Exception $e) {
            $this->di->getLog()->logContent('Errors : ' . $e->getMessage(),\Phalcon\Logger::CRITICAL,'product_after_save_exception.log');
        }
    }


    public function deleteOrder()
    {
        $orderJson = '/var/www/invalidOrderWithAmazonOrderId.json';
      $orderDatas = json_decode(file_get_contents($orderJson),true);
      $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
      $userCollections = $mongo->getCollectionForTable('user_details');

      $orderFile = '/var/www/order.json';

      $satyaSirdatas = json_decode(file_get_contents($orderFile),true);
   
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
      
     // $orderIdWiseCustomerResponse = [];
      $UnavailableShop = [];
      $uninstalledShop = [];

      $configuration = $mongo->getCollectionForTable(\App\Amazon\Components\Common\Helper::CONFIGURATION);

      $_customer = [];
      $_Orders = [];
      foreach ($needToWork as $orderId => $customers) 
      {
        echo $orderId." customer_id:";

        foreach ($customers as $customer => $attempt) {
            echo $customer;
            echo PHP_EOL;
          $remote_shop_id = 0;

          // $_customer[$customer] = $customer;

          $_Orders[$orderId] = $orderId;

          // if(!isset($remoteShopIds[$customer]))
          // {

            // $failedNotificationSetting = false;
            // $configurationSettings = $configuration->findOne(['user_id'=>$customer], ['typeMap'=>['root'=> 'array', 'document' => 'array']]);
            // if (isset($configurationSettings['data']['order_settings']['order_for_product_not_existing'])) {
            //     $failedNotificationSetting = $configurationSettings['data']['order_settings']['order_for_product_not_existing'];
            // }

            // if($failedNotificationSetting){
            //   $_customer[$customer] = $customer;
            // }

          //   if($userData)
          //   {

          //     $remoteShopIds[$customer] = $userData['shops'][0]['remote_shop_id'];
          //     $remote_shop_id = $remoteShopIds[$customer];
          //   }
          // } else {
          //   $remote_shop_id = $remoteShopIds[$customer];
          // }
          //die("vkkvv");


          // $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
          // ->init('shopify', 'true')
          // ->call('/order',['Response-Modifier'=>'0'],['id'=>$orderId,'shop_id'=>$remote_shop_id,'fields'=>'id,name','status'=>'any'], 'DELETE');
         // var_dump($remoteResponse);die;

       //   $orderIdWiseCustomerResponse[$orderId][$customer] = $remoteResponse;
          // if($remoteResponse && $remoteResponse['success'])
          // {
          //   $deleteUserFound[$customer][] = $orderId;
          // }

          // if($remoteResponse && !$remoteResponse['success'])
          // {
          //   if(isset($remoteResponse['msg']) && isset($remoteResponse['msg']['errors']))
          //   {
          //       if($remoteResponse['msg']['errors'] !='Not Found')
          //       {
          //           if($remoteResponse['msg']['errors'] =='Unavailable Shop')
          //           {
          //               $UnavailableShop[$customer][$orderId] = $remoteResponse;

          //           } else {
          //               $uninstalledShop[$customer][$orderId] = $remoteResponse;
          //           }
          //       }
          //   }
          // }
          // sleep(1);
        }

        //var_dump(json_encode($orderIdWiseCustomerResponse));

         echo PHP_EOL;
      }

      var_dump(json_encode($_Orders));die;

       echo PHP_EOL;
       var_dump('UnavailableShop:',json_encode($UnavailableShop));
       var_dump('uninstalledShop:',json_encode($uninstalledShop));

      var_dump(json_encode($deleteUserFound));die;
    }
}