<?php
namespace App\Connector\Controllers;

use App\Core\Models\BaseMongo;

class OverviewController extends \App\Core\Controllers\BaseController
{
    public function productAction()
    {
        $params = $this->di->getRequest()->get();
        $response = [];
        if(!empty($params['marketplace'])){
            $userId = $this->di->getUser()->id;
            $appCode = $params['app_code'];

            $modelObj = new BaseMongo();
            $collection = $modelObj->getCollectionForTable('user_details');
            $userDetailQuery = [];
            $userDetailQuery[] = [
                '$unwind' => '$shops',
            ];

            $userDetailQuery[] = [
                    '$match' => [
                        "user_id" => $userId,
                        "shops.marketplace" => $params['marketplace']
                    ]
            ];

            $userDetailQuery[] = [
                    '$project' => [
                        "shops._id" => 1,
                        "shops.warehouses" => 1
                    ]
            ];
            $response = $collection->aggregate($userDetailQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
            $response = $response->toArray();
            

            if(!empty($response))
            {
                $data = [];
                
                foreach ($response as $key => $shopData) {
                   
                    $notUploadedProduct = 0;
                    $shop_id = $shopData['shops']['_id'];
                    $warehouseData = [];
                    $wareHouseInfo = [];

                    foreach ($shopData['shops']['warehouses'] as $key => $warehouse) {
                       /* if($warehouse['status']=='active')
                        {*/
                            $warehouseData[$shop_id]["seller_id"] = $warehouse['seller_id'];
                            $wareHouseInfo[$warehouse['seller_id']] = $warehouse;
                        /*}*/
                    }

                    if(!empty($warehouseData)) {
                        $productCollections = $modelObj->getCollectionForTable('product_container');
                        


                        $totalCount = $productCollections->count([
                                    "user_id" => $userId,
                                    'app_codes'=>['$in' => [$appCode]],
                                    'group_id'=>['$exists'=>false]
                                ]);

                        $productDetailQuery = [];
                        $productDetailQuery[] = [
                                '$match' => [
                                    "user_id" => $userId,
                                    'app_codes'=>['$in' => [$appCode]],
                                    'group_id'=>['$exists'=>false]
                                   // 'marketplace.'.$params['marketplace']=>['$exists'=>true]
                                ]
                        ];

                        $productDetailQuery[] = [
                                '$unwind' => '$marketplace.'.$params['marketplace']
                        ];

                        $productDetailQuery[] = [
                                '$match' => [
                                        '$or'=>[
                                            [
                                                'marketplace.'.$params['marketplace'].'.shop_id' => (string)$shop_id
                                            ],
                                            [
                                                'marketplace.'.$params['marketplace'].'.shop_id' => (int)$shop_id
                                            ],
                                            [
                                                'marketplace.'.$params['marketplace'].'.shop_id'=>['$exists'=>false]
                                            ]
                                        ]
                                ]
                        ];

                        $productDetailQuery[] = [
                                '$group' => [
                                    "_id" => '$marketplace.'.$params['marketplace'].'.status',
                                    "count" => [
                                        '$sum'=>1
                                    ]
                                ]
                        ];
                    //    print_r($productDetailQuery);die;


                    /*    $productDetailQuery[] = [
                                '$group' => [
                                    "_id" => '$marketplace.'.$params['marketplace'].'.'.$shop_id.'.status',
                                    "count" => [
                                        '$sum'=>1
                                    ]
                                ]
                        ];*/
                       // print_r($productDetailQuery);die;


                        $response = $productCollections->aggregate($productDetailQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
                        $response = $response->toArray();
                       // $response[] = ['_id' => null, 'count' => $totalCount];

                        $countWithStatus = 0;
                        //print_r($response);die;

                        if(!empty($response))
                        {
                            foreach ($response as $key => $count) {
                                

                                if(!empty($count['_id']))
                                {
                                    $count['addtional_data'] = $wareHouseInfo[$warehouseData[$shop_id]["seller_id"]];

                                    $data[$shop_id][$warehouseData[$shop_id]["seller_id"]][] = $count;
                                    $countWithStatus = $countWithStatus+$count['count'];
                                } /*elseif(!is_string($count['_id']))
                                {

                                    $notUploadedProduct = $notUploadedProduct+$count['count'];
                                }*/
                            }
                           // var_dump($countWithStatus,$data);die;

                            $notUploadedProduct = $totalCount-$countWithStatus;

                            if($notUploadedProduct)
                            {
                                $addtionalData = $wareHouseInfo[$warehouseData[$shop_id]["seller_id"]];

                                $data[$shop_id][$warehouseData[$shop_id]["seller_id"]][] = ['_id'=>'NotListed','count'=>$notUploadedProduct,'addtional_data'=>$addtionalData];
                            }
                            
                        } else {
                            $addtionalData = $wareHouseInfo[$warehouseData[$shop_id]["seller_id"]];
                           // $data[$shop_id][$warehouseData[$shop_id]["seller_id"]][] = ['_id'=>'NotListed','count'=>$totalCount];
                            $data[$shop_id][$warehouseData[$shop_id]["seller_id"]][] = ['_id'=>'NotListed','count'=>$totalCount,'addtional_data'=>$addtionalData];
                        }

                    }
                } 
                if(!empty($data))
                {
                    $finalData = [];

                    foreach ($data as $shop_id => $data) {
                        $finalData = array_merge($finalData,$data);
                    }

                    $status_array = ['Active', 'Available for Offer', 'Inactive', 'Incomplete', 'NotListed', 'Supressed', 'Uploaded'];
                    foreach ($finalData as $amazonSellerId => $productStatusCount)
                    {
                        $addtionalData = current($productStatusCount)['addtional_data'];
                        foreach ($status_array as $status_code) 
                        {
                            $statusExists = false;
                            foreach ($productStatusCount as $statusData) {
                                if($statusData['_id'] == $status_code) {
                                    $statusExists = true;
                                    break;
                                }
                            }

                            if(!$statusExists) {
                                $finalData[$amazonSellerId][] = [
                                    '_id'            => $status_code,
                                    'count'          => 0,
                                    'addtional_data' => $addtionalData
                                ];
                            }
                        }
                    }

                    $response = ['success'=>true,'data'=>$finalData];
                } else {
                     $response = ['success'=>false,'message'=>'no data found'];
                }
            } else {
                $response = ['success'=>false,'message'=>'shop not found'];
            }

            
        } else {
            $response = ['success'=>false,'message'=>'marketplace not set'];
        }
        return $this->prepareResponse($response);
    }

    public function orderAction()
    {
        $params = $this->di->getRequest()->get();
        $response = [];
        if(!empty($params['marketplace']) && $params['status']){
            $userId = $this->di->getUser()->id;

            $modelObj = new BaseMongo();
            $collection = $modelObj->getCollectionForTable('user_details');
            $userDetailQuery = [];
            $userDetailQuery[] = [
                '$unwind' => '$shops',
            ];

            $userDetailQuery[] = [
                    '$match' => [
                        "user_id" => $userId,
                        "shops.marketplace" => $params['marketplace']
                    ]
            ];

            $userDetailQuery[] = [
                    '$project' => [
                        "shops._id" => 1,
                        "shops.warehouses" => 1
                    ]
            ];
            $response = $collection->aggregate($userDetailQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
            $response = $response->toArray();
           


            if(!empty($response))
            {
                $data = [];

                foreach ($response as $key => $shopData) {
                    
                    $shop_id = $shopData['shops']['_id'];
                    $warehouseData = [];

                    $wareHouseInfo = [];

                    foreach ($shopData['shops']['warehouses'] as $key => $warehouse) {
                      /*  if($warehouse['status']=='active')
                        {*/
                            $warehouseData[$shop_id]["seller_id"] = $warehouse['seller_id'];
                            $wareHouseInfo[$warehouse['seller_id']] = $warehouse;
                      /*  }*/
                    }

                    if(!empty($warehouseData)) {
                        
                        $orderCollections = $modelObj->getCollectionForTable('order_container');
                        $orderDetailQuery = [];
                        $orderDetailQuery[] = [
                                '$match' => [
                                   "user_id" => $userId,
                                    "target_status"=> $params['status'],
                                    "source"=>$params['marketplace'],
                                    "shop_id"=>(string)$shop_id
                                ]
                        ];

                        $response = $orderCollections->aggregate($orderDetailQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
                        $response = $response->toArray();

                        if(!empty($response))
                        {
                            foreach ($response as $key => $order_data) {
                                if (isset($order_data['url'])) {
                                    $url = $order_data['url'];
                                } else {
                                    $url = '';
                                }
            
                                $data[$shop_id][$warehouseData[$shop_id]["seller_id"]][$order_data['source_order_id']] = ['source_order_id'=>$order_data['source_order_id'],'source_error_message'=>$order_data['source_error_message'],'target_error_message'=>$order_data['target_error_message'],'addtional_data'=>$wareHouseInfo[$warehouseData[$shop_id]["seller_id"]], 'url' => $url];
                            }
                        }

                    }
                }

                if(!empty($data))
                {
                    $finalData = [];

                    foreach ($data as $shop_id => $data) {
                        $finalData = array_merge($finalData,$data);
                    }
                    $response = ['success'=>true,'data'=>$finalData];
                } else {
                     $response = ['success'=>false,'message'=>'no data found'];
                }
            } else {
                $response = ['success'=>false,'message'=>'shop not found'];
            }

            
        } else {
            $response = ['success'=>false,'message'=>'marketplace/status not set'];
        }
        return $this->prepareResponse($response);
    }

    public function getActivitiesAction()
    {
        try {
            $activities = [];

            $user_id = $this->di->getUser()->id;

            $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');

            $helper = $this->di->getObjectManager()->get('\App\Frontend\Components\AmazonebaymultiHelper');
            $accounts = $helper->getAllConnectedAcccounts($user_id);
            if($accounts['success'])
            {
                $data = $accounts['data'];
                if($data) {
                    $shopifyCurrency = $amazonCurrency = '';

                    foreach ($data as $account) {
                        if($account['marketplace'] == 'shopify') {
                            $shopifyCurrency = $account['shop_details']['currency'];
                        }
                        elseif($account['marketplace'] == 'amazon' && $account['warehouses'][0]['status'] == 'active') {
                            $amazonCurrency = \App\Amazon\Components\Common\Helper::MARKETPLACE_CURRENCY[$account['warehouses'][0]['marketplace_id']];
                        }
                    }

                    if($shopifyCurrency && $amazonCurrency && $shopifyCurrency !== $amazonCurrency) 
                    {
                        $configuration = $mongo->getCollectionForTable(\App\Amazon\Components\Common\Helper::CONFIGURATION)
                                            ->findOne(['user_id' => $user_id], ["typeMap" => ['root' => 'array', 'document' => 'array']]);

                        if (!isset($configuration['data']['currency_settings']['settings_enabled']) || !$configuration['data']['currency_settings']['settings_enabled']) {
                            $activities[] = [
                                'activity'  => 'currency_setting',
                                'message'   => "'Global Price Adjustment' Setting is required because your Shopify Store Currency and Amazon Store Currency are different.",
                                // 'type'      => 'error'
                                'type'      => 'warning'
                            ];
                        }    
                    }
                }
            }

            $queuedTaskCollection = $mongo->getCollectionForTable('queued_tasks');

            $filter = [
                'user_id' => $user_id,
                // 'app_tag' => 'amazon'
            ];

            $tasks = $queuedTaskCollection->find($filter, ['typeMap'=>['root'=>'array', 'document'=>'array']]);

            foreach ($tasks as $task) {
                if(isset($task['type']) && ($task['type']=='product_import' || $task['type']=='saleschannel_product_import')) {
                    $activities[] = [
                        'activity'  => 'product_import',
                        'message'   => 'We are importing your products, please wait for some time.',
                        'type'      => 'info'
                    ];
                }
                else {
                    $activities[] = [
                        'activity'  => $task['type'],
                        'message'   => $task['message'],
                        'type'      => 'info'
                    ];
                }
            }

            $response = ['success' => true, 'data' => $activities];
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $this->prepareResponse($response);
    }
}