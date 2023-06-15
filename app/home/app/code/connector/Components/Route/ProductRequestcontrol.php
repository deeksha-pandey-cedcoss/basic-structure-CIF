<?php

namespace App\Connector\Components\Route;

use Exception;

class ProductRequestcontrol extends \App\Core\Components\Base
{ 
    
    // private $_baseMongo;
    // public function _construct()
    // {
    //     $this->_baseMongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');   
    // }
    
    public function handleUpload($sqsData){

        print_r($sqsData);
        die("helo");
        
        $targetMarketplace=$sqsData['data']['data']['target_marketplaces'];
        $sourceMarketplace=$sqsData['data']['data']['source_marketplaces'];
        $ids['source_product_ids']=$sqsData['data']['data']['product_ids'];
        $shop=$sqsData['data']['shop'];
        $user_id=$sqsData['data']['user_id'];
        $operation_type=$sqsData['data']['operation'];
        $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($shop['marketplace'])->get('source_model'));
        // if(method_exists($model, 'prepareProductUploadData')){
        //         $model->prepareProductUploadData($targetMarketplace,$sourceMarketplace,$ids,$shop,$user_id,$operation_type);
        //     }
        //     else{
        //         die("something is wrong");
        //     }
        // $mongoObject=$this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        // $productContainer=$mongoObject->getCollectionForTable('product_container');//object created.
        // foreach ($ids as $key => $id) {
        //     $product=$productContainer->find(['source_product_id' => $id, 'user_id' => $user_id,'source_marketplace'=>$sourceMarketplace], ["typeMap" => ['root' => 'array', 'document' => 'array']])->toArray();
        //     if ($product['type'] == 'variation' && !isset($product['group_id'])) {
        //         die("test");
        //     }else{

        //     }
        //     print_r($product);
        //     die(" test");
        //     $categorySettings = false;
        //     $parentCategorySettings = false;
           
        // }
        // die(" helo");
        // print_r($productContainer);
        // die(" test here");
        // try{ 
        //     // $productContainer = $this->_baseMongo->getCollectionForTable('product_container');
        //     print_r($this->_baseMongo);
        //     // print_r($productContainer);
        //     die(" hello");
        // }catch(Exception $e){
        //     print_r($e->getMessage());
        // }
        

        // $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($shop['marketplace'])->get('source_model'));
        // if(method_exists($model, 'prepareProductUploadData')){
        //     $model->prepareProductUploadData($targetMarketplace,$sourceMarketplace,$shop,$product_ids);
        // }

        // print_r($sqsData);
        // die("test got it");
    }
    public function handleImport($sqsData){
        $operation = $sqsData['data']['operation'];
        print_r($operation);
        switch($operation){
            case 'import_products_tempdb' :
                $response  = $this->ImportProductsInTempdb($sqsData);
                if(isset($response['requeue']) && $response['requeue']) {
                    $this->di->getMessageManager()->pushMessage($response['sqs_data']);
                }
                break;

            case 'tempdb_to_maindb' :
                $response  = $this->ImportInProductContainer($sqsData);
                if(isset($response['requeue']) && $response['requeue']) {
                    $this->di->getMessageManager()->pushMessage($response['sqs_data']);
                }
                break;

            default : break;
        }
        return true;
    }

    public function ImportProductsInTempdb($sqsData){
        $shop = $sqsData['data']['shop'];
        $marketplace = $shop['marketplace'];

        $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($shop['marketplace'])->get('source_model'));
        $marketplaceProductsRes = $model->getMarketplaceProducts($sqsData);
        if(isset($marketplaceProductsRes['success']) && $marketplaceProductsRes['success']){
            if ($marketplaceProductsRes['success'] && !empty($marketplaceProductsRes['products']['pushToTempdb'])){
                $this->pushInTempContainer($marketplaceProductsRes['products']['pushToTempdb'],$marketplace);
                if (isset($marketplaceProductsRes['nextcursor'])){
                    $sqsData['data']['cursor'] =$marketplaceProductsRes['nextcursor'];
                }else{
                    $sqsData['data']['operation'] = 'tempdb_to_maindb';
                    $sqsData['data']['limit'] = 250;
                    $sqsData['data']['cursor'] = 0;
                    return [ 'success' => true, 'requeue' => true, 'sqs_data' => $sqsData ];
                }
            }
            if ($marketplaceProductsRes['success'] && !empty($marketplaceProductsRes['products']['pushToMaindb'])){
                $additional_data['app_code'] = $sqsData['data']['app_code'];
                $additional_data['shop_id'] = $shop['_id'];
                $additional_data['marketplace'] = $marketplace;
                $additional_data['feed_id'] = $sqsData['data']['feed_id'];
                if (isset( $sqsData['data']['target_marketplace'] , $sqsData['data']['target_shop_id'])){
                    $additional_data['target_marketplace'] = $sqsData['data']['target_marketplace'];
                    $additional_data['target_shop_id'] = $sqsData['data']['target_shop_id'];
                }
                $this->pushToProductContainer($marketplaceProductsRes['products']['pushToMaindb'], $additional_data);
                if (isset($marketplaceProductsRes['nextcursor'])){
                    $sqsData['data']['cursor'] =$marketplaceProductsRes['nextcursor'];
                }else{
                    return [ 'success' => false, 'requeue' => false, 'sqs_data' => $sqsData ];
                }
            }else{
                return [ 'success' => false, 'requeue' => false, 'sqs_data' => $sqsData ];
            }
            return [ 'success' => true, 'requeue' => true, 'sqs_data' => $sqsData ];
        }else{
            return ['success'=>false,'message'=>"Products not getting from source marketplace"];
        }
    }

    public function ImportInProductContainer($sqsData){
        $marketplace = $sqsData['data']['shop']['marketplace'];
        print_r(" INSIDE ImportInProductContainer");
        $products = $this->getProductsFromTmpDb($sqsData,$marketplace);
        if (count($products)>0){
            $additional_data['app_code'] = $sqsData['data']['app_code'];
            $additional_data['shop_id'] =  $sqsData['data']['shop']['_id'];
            $additional_data['marketplace'] =  $marketplace;
            $additional_data['feed_id'] = $sqsData['data']['feed_id'];
            if (isset( $sqsData['data']['target_marketplace'] , $sqsData['data']['target_shop_id'])){
                $additional_data['target_marketplace'] = $sqsData['data']['target_marketplace'];
                $additional_data['target_shop_id'] = $sqsData['data']['target_shop_id'];
            }
            $this->pushToProductContainer($products, $additional_data);
            return [ 'success' => true, 'requeue' => true, 'sqs_data' => $sqsData ];
        }else{
            //updating progress and adding notification
            $queuedTask = \App\Connector\Models\QueuedTasks::findFirst([["_id" => $sqsData['data']['feed_id']]]);
            $progress = $this->di->getObjectManager()->get('\App\Connector\Components\Helper')->updateFeedProgress($sqsData['data']['feed_id'], 100);
            // for initiating Product Lookup and Product status Sync
            if (isset( $sqsData['data']['target_marketplace'] , $sqsData['data']['target_shop_id'])){
                $eventData['source_marketplace'] = $sqsData['data']['shop']['marketplace'];
                $eventData['source_shop_id'] =  $sqsData['data']['shop']['_id'];
                $eventData['target_marketplace'] = $sqsData['data']['target_marketplace'];
                $eventData['target_shop_id'] = $sqsData['data']['target_shop_id'];
                $eventsManager = $this->di->getEventsManager();
                $eventsManager->fire('application:afterProductImport', $this,$eventData);
            }
            if ($progress && $progress == 100){
                $this->di->getObjectManager()->get('\App\Connector\Components\Helper')->addNotification($sqsData['data']['user_id'], $queuedTask->message, 'success');
            }
        }

        return true;
    }

    public function pushInTempContainer($productData,$marketplace)
    {
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $tempCollection = $mongo->getCollectionForTable($marketplace.'_product_container');
        foreach ($productData as $key=>$product){
            $tempCollection->insertOne($product);
        }

        //todo: START set is_parent
        $filter = ['user_id' => $this->di->getUser()->id,'type'=>'variation'];
        $parent_ids = $tempCollection->distinct(
            "container_id",
            $filter
        );
        $tempCollection->updateMany(
            ['source_product_id'=>['$in'=>$parent_ids]],
            [ '$set' => ['is_parent'=>true]]
        );
        //todo: END set is_parent

    }

    public function getProductsFromTmpDb($sqsData,$marketplace){
        print_r("Inside getProductsFromTmpDb");
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $tempCollection = $mongo->getCollectionForTable($marketplace.'_product_container');
        $limit = $sqsData['data']['limit'];
        $filter = [
            'user_id'=>$sqsData['user_id']
        ];
        print_r($limit);
        $options = ['limit' => $limit, "typeMap" => ['root' => 'array', 'document' => 'array']];
        $simpleProduct = $tempCollection->find($filter,$options)->toArray();
        return $simpleProduct;
    }

    public function pushToProductContainer($products, $additional_data=[])
    {
        $marketplace = $additional_data['marketplace'];
        $shop_id = $additional_data['shop_id'];
        $productContainer = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $tempCollection = $mongo->getCollectionForTable($marketplace.'_product_container');
        $mongoCollection = $mongo->getCollectionForTable('product_container');

        $simpleProductCounter = 0;
        $bulkOpArray = [];
        $tempdbOpArray = [];

        foreach ($products as $productData){
            $exists = $mongoCollection->findOne([
                '$and'=>[
                    ['user_id'=>$this->di->getUser()->id],
                    ['source_product_id' => (string)$productData['source_product_id']]
                ]
            ]);
            if(!empty($exists)){
                $productData['db_action'] = "variant_update";
                $data = $productData;
                if(isset($additional_data['app_code'])) {
                    $app_code = $additional_data['app_code'];
                    $existingProduct = json_decode(json_encode($exists), true);
                    if(isset($existingProduct['app_codes'])) {
                        if(!in_array($app_code, $existingProduct['app_codes'])) {
                            $existingProduct['app_codes'][] = $app_code;
                            $data['app_codes'] = $existingProduct['app_codes'];
                        }
                    }
                    else {
                        $data['app_codes'] = [$app_code];
                    }
                }
                $data['shop_id'] = $shop_id;
                $data['source_marketplace'] = $marketplace;
                $query = [
                    '$and'=>[
                        ['source_product_id' => $data['source_product_id']],
                        ['user_id'=>$this->di->getUser()->id]
                    ]
                ];
                unset($data['_id']);
                $eventsManager = $this->di->getEventsManager();
                $eventsManager->fire('application:productSaveBefore', $this, ['product_data'=>&$data]);
                $bulkOpArray[] = [
                    'updateOne' => [
                        $query,
                        [ '$set' => $data]
                    ]
                ];
                $tempdbOpArray[] = [
                    'deleteOne' => [
                        $query
                    ]
                ];
            } else {
                $variantData = $this->getChildProducts($productData,$marketplace);
                $data['details'] = $this->di->getObjectManager()->get("\App\Connector\Components\Helper")->formatCoreDataForDetail($productData);
                $data['variant_attribute'] = $variantData['variant_attributes'];
                foreach ($variantData['variants'] as $key=>$variant){
                    unset($variant['_id']);
                    $variants = $variant;
                    if(isset($additional_data['app_code'])) {
                        $app_code = $additional_data['app_code'];
                        $data['details']['app_codes'] = [$app_code];
                        $variants['app_codes'] = [$app_code];
                    }
                    if(isset($variants['variant_attributes']) && count($variants['variant_attributes']) > 0) {
                        $data['details']['type'] = 'variation';
                    }
                    $data['variants'][] = $variants;
                }
                $productContainer->setSource("product_container");
                $shop_id = $additional_data['shop_id'] ?? 0;
                $targetEventData = [];
                if (isset( $additional_data['target_marketplace'] , $additional_data['target_shop_id'])){
                    $targetEventData['target_marketplace'] = $additional_data['target_marketplace'];
                    $targetEventData['target_shop_id'] = $additional_data['target_shop_id'];
                }
                $res = $productContainer->createProductsAndAttributes([$data], $marketplace, $shop_id, $this->di->getUser()->id,$targetEventData);
                print_r($res);
                if(isset($res[0]['success']) && $res[0]['success']) $simpleProductCounter++;
            }
            $data = [];
        }
        try{
            if (isset($additional_data['feed_id'])){
                $filter =['$and'=>[
                    ['source_marketplace' => $marketplace],
                    ['user_id'=>$this->di->getUser()->id],
                    ["visibility" => 'Catalog and Search']
                ]];

                $productCount =  $mongoCollection->count($filter);
                $message = $productCount." Products imported successfully.";
                $this->di->getObjectManager()->get('\App\Connector\Components\Helper')->updateFeedProgress($additional_data['feed_id'], 0,$message);
            }

            if(!empty($bulkOpArray)){
                $bulkObj = $mongoCollection->BulkWrite($bulkOpArray, ['w' => 1]);
                print_r($bulkObj);
                print_r(" inside bulk write condition");
                if (!empty($tempdbOpArray)) $tempCollection->BulkWrite($tempdbOpArray, ['w' => 1]);
                $returenRes = [
                    'acknowledged' => $bulkObj->isAcknowledged(),
                    'inserted' => $bulkObj->getInsertedCount(),
                    'modified' => $bulkObj->getModifiedCount(),
                    'matched' => $bulkObj->getMatchedCount(),
                    'mainProductCount' => $simpleProductCounter
                ];
                return [
                    'success' => true,
                    'stats' => $returenRes
                ];
            }
        } catch (\Exception $e)
        {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return [
            'success' => true,
            'stats' => $simpleProductCounter. ' main product imported successfully'
        ];

    }

    public function getChildProducts($parentData,$marketplace){
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $tempCollection = $mongo->getCollectionForTable($marketplace.'_product_container');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        if (isset($parentData['is_parent'])) {
            $filter = [
                'user_id' => $parentData['user_id'],
                'container_id' => $parentData['container_id'],
                'type'=>"simple"
            ];
            $variants = $tempCollection->find($filter, $options)->toArray();
        }else{
            $filter = [
                'user_id' => $parentData['user_id'],
                'container_id' => $parentData['container_id'],
            ];
            $variants = [$parentData];
        }
        // set is_child
        $tempCollection->deleteMany(
            $filter
        );
        return ['variant_attributes'=>isset($variants[0]['variant_attributes']) ? $variants[0]['variant_attributes'] :[] ,'variants'=>$variants];
    }

}
