<?php

namespace App\Connector\Models\Product;

use App\Core\Models\Base;
use Phalcon\Mvc\Model\Message;


class Edit extends Base
{
    protected $table = 'product_container';

    public function getProduct($data)
    {
        $userId = $this->di->getUser()->id;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->setSource("product_container")->getPhpCollection();
        $aggregate = [];
        $product = [];
        if (isset($data['source_product_id'])) {
            $aggregate[] = ['$match' => [
                'source_product_id' => $data['source_product_id'],
                'user_id' => $userId,
                '$or' => [
                    [
                        'shop_id' => $data['sourceShopID'],
                        'source_marketplace' => 'onyx',
                    ],
                    [
                        'shop_id' => $data['targetShopID'],
                        'target_marketplace' => 'amazon'
                    ]
                ]
            ]];
            $product = $collection->aggregate($aggregate)->toArray();
        } elseif (isset($data['container_id'])) {
            $aggregate[] = ['$match' => [
                'container_id' => $data['container_id'],
                'user_id' => $userId,
                '$or' => [
                    [
                        'shop_id' => $data['sourceShopID'],
                        'source_marketplace' => 'onyx',
                    ],
                    [
                        'shop_id' => $data['targetShopID'],
                        'target_marketplace' => 'amazon'
                    ]
                ]
            ]];
            $product = $collection->aggregate($aggregate)->toArray();
        }
        $variantProduts = [];
        $mappedProducts = [];
        foreach ($product as $key => $value) {
            if (isset($value['source_product_id']) && isset($value['container_id'])) {
                $toMerge = isset($value['target_marketplace']) == 'amazon' ? ['edited' => $value] : $value;
                $mappedProducts[$value['source_product_id']] = isset($mappedProducts[$value['source_product_id']]) ? array_merge((array)$mappedProducts[$value['source_product_id']], (array)$toMerge) : $toMerge;
                if ($value['source_product_id'] != $value['container_id']) {
                    $variantProduts[$value['source_product_id']] = isset($mappedProducts[$value['source_product_id']]) ? array_merge((array)$mappedProducts[$value['source_product_id']], (array)$toMerge) : $toMerge;
                }
            }
        }
        return ['success' => true, 'data' => ['rows' => array_values($mappedProducts), 'variant_products' => array_values($variantProduts), 'user_id' => $userId]];
    }

    public function saveProduct($data)
    {
        $userId = $this->di->getUser()->id;
        // $data['user_id'] = $userId;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $productContainer = $mongo->setSource("product_container")->getPhpCollection();

        $barcode = $this->di->getObjectManager()->create('App\Amazon\Components\Common\Barcode');
        $helper = $this->di->getObjectManager()->get('\App\Connector\Models\Product\Marketplace');

        $barcodeFail = [];
        $bulkOpArray = [];
        $skuArray = [];
        foreach ($data as $key => $value) {
            if (isset($value['source_product_id'])) {
                $validateBarcode = true;
                if (isset($value['barcode'])) {
                    $validateBarcode = $barcode->setBarcode($value['barcode']);
                }
                if ($validateBarcode == false) {
                    $barcodeFail[] = ['source_product_id' => $value['source_product_id'], 'sku' => $value['sku']];
                }

                $condition = [];
                // die(json_encode($value));
                if (isset($value['source_marketplace']) && isset($value['childInfo'])) {

                    $res = $helper->marketplaceSaveAndUpdate($value);
                    continue;
                } else {
                    $condition = [
                        'source_product_id' => $value['source_product_id'],
                        'container_id' => $value['container_id'],
                        'target_marketplace' => 'amazon',
                        'shop_id' => $value['shop_id'],
                        'user_id' => $userId
                    ];
                }
                if (isset($value['sku']) && !isset($value['marketplace'])) {
                    $skuArray[] = [
                        "source_product_id" => $value['source_product_id'], // required
                        'user_id' => $userId,
                        'childInfo' => [
                            'source_product_id' => $value['source_product_id'], // required
                            'shop_id' => $value['shop_id'], // required
                            'sku' => $value['sku'],
                            'target_marketplace' => 'amazon', // required
                        ]
                    ];
                } elseif (isset($value['marketplace'])) {

                    $prepareArr = $value['marketplace'];
                    $prepareArr['source_product_id'] = $value['source_product_id'];
                    $prepareArr['shop_id'] = $value['shop_id'];
                    $prepareArr['target_marketplace'] = 'amazon';
                    $ToupdateInMarketPlace = [
                        "source_product_id" => $value['source_product_id'], // required
                        'user_id' => $userId,
                        'childInfo' => $prepareArr
                    ];
                    $res = $helper->marketplaceSaveAndUpdate($ToupdateInMarketPlace);
                    unset($value['marketplace']);
                }
                $unset = (object)[];
                if (isset($value['unset'])) {
                    $unset = $value['unset'];
                    unset($value['unset']);
                }
                if (isset($value['shops'])) {
                    $shops = $value['shops'];
                    unset($value['shops']);
                    $tempShops = [];
                    foreach ($shops as $key => $val) {
                        if ($key == 'category_settings') {
                            foreach ($val as $catKey => $catVal) {
                                $tempShops['shops.category_settings.' . $catKey] = $catVal;
                            }
                        } else {
                            $tempShops['shops.' . $key] = $val;
                        }
                    }
                    $value = array_merge((array)$value, (array)$tempShops);
                }
                $value['user_id'] = $userId;
                $bulkOpArray[] = [
                    'updateOne' => [
                        (object)$condition, ['$set' => (object)$value, '$unset' => (object)$unset], ['upsert' => true]
                    ]
                ];
            }
        };
        $saveData = empty($barcodeFail) ? true : false;
        // die(json_encode($skuArray));
        if ($saveData == true) {
            foreach ($skuArray as $key => $value) {
                $res = $helper->marketplaceSaveAndUpdate($value);
            }
        }

        $repsonse = $saveData && $productContainer->BulkWrite($bulkOpArray, ['w' => 1]);

        return ['success' => $saveData, 'message' => $saveData ? 'Saved successfully' : 'Check Barcode Details', 'data' => ['barcode_fail' => $barcodeFail]];
    }
}
