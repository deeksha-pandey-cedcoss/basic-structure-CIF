<?php

namespace App\Connector\Models\Product;

use App\Core\Models\Base;


class Marketplace extends Base
{

    public $_mongo;
    public $_user_id;

    // in case, we decided to change it in future
    public $_child_key = 'marketplace';

    public function init($data)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $this->_mongo = $mongo->getCollectionForTable('product_container');

        if (isset($data['user_id'])) {
            $this->_user_id = $data['user_id'];
        } else {
            $this->_user_id = $this->di->getUser()->id;
        }
    }

    /**
     * $data = [
     * source_product_id: "string",
     * 'childInfo' = []
     * ]
     */
    public function marketplaceSaveAndUpdate($data)
    {
        $this->init($data);

        $dbData = $this->_mongo->findOne([
            'source_product_id' => $data['source_product_id'],
            'user_id' => $this->_user_id
        ]);

        echo "<pre>";

        $validate = $this->validateData($data['childInfo']);

        if (isset($validate['success']) && $validate['success'] === false) {
            return $validate;
        }

        if (is_object($dbData) && count($dbData) > 0) {
            if (isset($dbData[$this->_child_key])) {
                $this->updateChild($data, $dbData);
            } else {
                $this->insertChild($data, $dbData);
            }
            $this->checkForParent($data, $dbData);
        } else {
            return [
                'success' => false,
                'message' => 'Product Not Found!!',
            ];
        }
        return [
            'success' => true
        ];
    }

    public function validateData($data)
    {
        if (!isset($data['source_product_id']) || !isset($data['shop_id'])) {
            return ['success' => false, 'message' => 'source_product_id or shop_id is missing'];
        }
        if (!isset($data['source_marketplace']) && !isset($data['target_marketplace'])) {
            return ['success' => false, 'message' => 'source or target marketplace must be define'];
        }
        return ['success' => true];
    }

    public function checkForParent($data, $dbData)
    {
        if ($dbData['type'] === "simple"
            && $dbData['visibility'] === "Not Visible Individually"
            && isset($dbData['container_id'])) {
            $dbData = $this->_mongo->findOne([
                'source_product_id' => $dbData['container_id'],
                'user_id' => $this->_user_id
            ]);
            if (is_object($dbData) && count($dbData) > 0) {
                $data['source_product_id'] = $dbData['source_product_id'];
                if (isset($dbData[$this->_child_key])) {
                    $this->updateChild($data, $dbData);
                } else {
                    $this->insertChild($data, $dbData);
                }
            }
        }
        return true;
    }

    public function insertChild($var, $dbData)
    {
        $dbData[$this->_child_key] = [];
        if (isset($var['childInfo']['target_marketplace'])) {
            $var['childInfo']['direct'] = false;
        }
        $dbData[$this->_child_key][] = $var['childInfo'];
        $this->_mongo->updateOne([
            'source_product_id' => $var['source_product_id'],
            'user_id' => $this->_user_id,
            'source_marketplace' => [
                '$exists' => true
            ]
        ], [
            '$set' => [
                $this->_child_key => $dbData[$this->_child_key]
            ]
        ]);
        return true;
    }

    public function updateChild($var, $dbData)
    {
        $childInfo = $var['childInfo'];
        $flag = true;
        foreach ($dbData[$this->_child_key] as $key => $value) {
            if ($childInfo['source_product_id'] === $value['source_product_id']
                && $childInfo['shop_id'] === $value['shop_id']) {
                $flag = false;
                $dbData = json_decode(json_encode($dbData), true);
                $dbData[$this->_child_key][$key] = $childInfo + $dbData[$this->_child_key][$key];
            }
        }
        if ($flag) {
            $dbData[$this->_child_key][] = $var['childInfo'];
        }
        $this->_mongo->updateOne([
            'source_product_id' => $var['source_product_id'],
            'user_id' => $this->_user_id,
            'source_marketplace' => [
                '$exists' => true
            ]
        ], [
            '$set' => [
                $this->_child_key => $dbData[$this->_child_key]
            ]
        ]);
        return true;
    }

    /**
     * $data = ['source_product_id', 'shop_id', 'user_id']
     */
    public function getSingleProduct($data)
    {
        $this->init($data);

        $source_product = $this->_mongo->findOne([
            'source_product_id' => $data['source_product_id'],
            'user_id' => $this->_user_id
        ]);

        $target_product = $this->_mongo->findOne([
            'source_product_id' => $data['source_product_id'],
            'shop_id' => $data['shop_id'],
            'user_id' => $this->_user_id
        ]);

        $source_product = json_decode(json_encode($source_product), true);
        $source_product = !is_null($source_product) ? $source_product : [];
        $target_product = json_decode(json_encode($target_product), true);
        $target_product = !is_null($target_product) ? $target_product : [];

        return $target_product + $source_product;
    }

    /**
     * @param $data
     * @return array|mixed
     * $data = ['source_product_id', 'shop_id', 'user_id']
     */
    public function getProductBySku($data)
    {
        $this->init($data);

        $source_product = $this->_mongo->findOne([
            'sku' => $data['sku'],
            'user_id' => $this->_user_id
        ]);

        $target_product = $this->_mongo->findOne([
            'sku' => $data['sku'],
            'shop_id' => $data['shop_id'],
            'user_id' => $this->_user_id
        ]);

        $source_product = json_decode(json_encode($source_product), true);
        $source_product = !is_null($source_product) ? $source_product : [];
        $target_product = json_decode(json_encode($target_product), true);
        $target_product = !is_null($target_product) ? $target_product : [];

        $newProduct = ($target_product ?? []) + ($source_product ?? []);

        return $newProduct;
    }
}