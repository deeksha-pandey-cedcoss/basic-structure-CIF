<?php

namespace App\Connector\Components;

use Phalcon\Events\Event;

class ProductEvent extends \App\Core\Components\Base
{

    /**
     * @param Event $event
     * @param $myComponent
     */



    public function productSaveBefore(Event $event, $myComponent,$actualData)
    {
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $productContainer = $mongo->getCollectionForTable('product_container');
        $filter = [
            'user_id'=>$this->di->getUser()->id,
            'source_sku'=>$actualData['product_data']['source_sku']
        ];
        $product_sku_count = $productContainer->count($filter);
        if ($product_sku_count>0) {
            $checkSkuUpdated = $this->checkSkuUpdate($actualData['product_data']);
            if ($checkSkuUpdated['success']){
                $counter = (string) $mongo->getCounter($actualData['product_data']['source_sku'],$this->di->getUser()->id);
                $actualData['product_data']['sku'] = $actualData['product_data']['source_sku'] . '_' .$counter;
                $actualData['product_data']['low_sku'] = strtolower($actualData['product_data']['sku']);
            }else{
                $actualData['product_data']['sku'] = $checkSkuUpdated['sku'];
                $actualData['product_data']['low_sku'] = strtolower($checkSkuUpdated['sku']);
            }
        }else{
            $actualData['product_data']['sku'] = $actualData['product_data']['source_sku'];
            $actualData['product_data']['low_sku'] = strtolower($actualData['product_data']['sku']);
        }

        // for inserting Filters in marketplace array
        $marketplace = [];
        $filters = $this->di->getConfig()->allowed_filters ? $this->di->getConfig()->allowed_filters->toArray() : [];
        if (isset($actualData['product_data']['type']) && $actualData['product_data']['type']=="simple" ){
            foreach ($filters as $key=>$filter){
                if (isset($actualData['product_data'][$filter]))
                    $marketplace[$filter] = $actualData['product_data'][$filter];
                $marketplace['direct'] = true;
            }
            if (isset($actualData['product_data']['source_product_id'],$actualData['product_data']['container_id']) && $actualData['product_data']['source_product_id']!=$actualData['product_data']['container_id']){
                 $this->updateParentProduct($actualData['product_data'],$marketplace);
            }
            $actualData['product_data']['marketplace'] = [$marketplace];
        }
        $this->formatVariantAttribute($actualData['product_data']);

        if (isset($actualData['product_data']['source_product_id'],$actualData['product_data']['container_id']) && $actualData['product_data']['source_product_id']==$actualData['product_data']['container_id']){
            unset($actualData['product_data']['price'],$actualData['product_data']['quantity']);
        }
    }

    public function formatVariantAttribute(&$productData){
        if (isset($productData['variant_attributes']) && !empty($productData['variant_attributes'])){
            $updatedVariantAttri = [];
            foreach ($productData['variant_attributes'] as $key=>$variant_attribute){
               $attribute = [
                   "key"=>$variant_attribute,
                   "value" => isset($productData[$variant_attribute]) ? $productData[$variant_attribute] : ""
               ];
               array_push($updatedVariantAttri, $attribute);
               if (isset($productData[$variant_attribute])) unset($productData[$variant_attribute]);
            }
            $productData['variant_attributes'] = $updatedVariantAttri;
        }
    }

    public function checkSkuUpdate($productData){
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $productContainer = $mongo->getCollectionForTable('product_container');
        $filter = [
            'user_id'=>$this->di->getUser()->id,
            'source_sku'=>$productData['source_sku'],
            'source_product_id'=>$productData['source_product_id'],
        ];
        $product = $productContainer->findOne(
            $filter,
            ["typeMap" => ['root' => 'array', 'document' => 'array']]
        );
        if (!empty($product) && isset($product['source_sku'],$productData['source_sku']) && $product['source_sku']==$productData['source_sku']){
            return ['success'=>false,'message'=>"Product SKU not Updated",'sku'=>$product['sku']];
        }else{
            return ['success'=>true,'message'=>"Product SKU need Updated"];
        }
    }

    public function updateParentProduct($productData,$marketplace){
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $productContainer = $mongo->getCollectionForTable('product_container');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $parent_filter = [
            'user_id' => $productData['user_id'],
            'source_product_id' => $productData['container_id'],
        ];

        $parentProduct = $productContainer->findOne($parent_filter, $options);
        $allChildEntries = $parentProduct['marketplace'] ?? [];

        if (isset($productData['source_product_id'],$productData['container_id']) && $productData['source_product_id']!=$productData['container_id']) {
            array_push($allChildEntries,$marketplace);
            $productContainer->updateOne($parent_filter, ['$set' => ['marketplace' => $allChildEntries]]);
            return ['success'=>true,'message'=>"Parent updated"];
        }
    }

    public function productSaveAfter(Event $event, $myComponent,$data)
    {

    }
}
