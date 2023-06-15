<?php

namespace App\Connector\Models\Profile;

use App\Core\Models\BaseMongo;

class UploadHelper extends BaseMongo
{
    public $process_data;
    public $profile_id;
    protected $profile_data;
    protected $attributeMappingPath = '';
    protected $product_data = null;
    public function validateData()
    {
        $requiredKey = $this->requiredKey();
        foreach ($requiredKey as $key => $value) {
            if (isset($this->process_data[$value])) {
                $this->{$value} = $this->process_data[$value];
            } else {
                return ['success' => false, 'message' => "Required Key missing"];
            }
        }

    }

    public function requiredKey()
    {
        return [
            'profile_id',
            'target_marketplace',
        ];

    }

    public function process()
    {
        $validateData = $this->validateData();
        if (isset($validateData['success']) && !$validateData['success']) {
            return $validateData['message'];
        }
        $profileData = $this->getProfileData();
        if (isset($profileData['success']) && $profileData['success']) {
            $response = $this->targetMarketplaceProcess();
            return $response;
        } else {
            return $profileData;
        }

    }

    public function getProfileData()
    {
        $profileParams = [];
        $profileParams['filters'] = ['id' => $this->profile_id];
        $obj = new Model();
        $profileData = $obj->getProfile($profileParams);

        if (isset($profileData['data'])) {
            if (isset($profileData['data'][0])) {
                $this->profile_data = $profileData['data'][0];
                return ['success' => true, "data" => $this->profile_data];
            } else {
                return ['success' => false, "message" => "profile_id not found"];
            }

        }
        return ['success' => false, "message" => "profile_id not found"];
    }

    public function getProductData()
    {

        if (is_null($this->product_data)) {
            $profileData = [];
            var_dump($this->process_data['page']);
            var_dump($this->process_data['limit']);
            if (isset($this->process_data['page'], $this->process_data['limit'])) {
                $limit = (int) $this->process_data['limit'];
                $page = (int) $this->process_data['page'] - 1;
                $skip = ($limit * $page);
            } else {
                $skip = 0;
                $limit = 50;
            }
            $profileData['skip'] = $skip;
            $profileData['limit'] = $limit;
            $obj = new Helper();
            $profileData['marketplace'] = $this->process_data['target_marketplace'];
            $profileData['profile_data'] = $this->profile_data;
            $obj->process_data = $profileData;

            $searchProduct = $obj->getProducts();
            if ($searchProduct['success']) {
                $searchProduct = $searchProduct['data'];
                $this->product_data = $searchProduct;
            } else {
                return ['success' => false, "message" => "product not found for this profile id"];
            }
        } else {
            $searchProduct = $this->product_data;
        }

        if ($searchProduct) {
            return ['success' => true, "data" => $searchProduct];
        } else {
            return ['success' => false, "message" => "product not found for this profile id"];
        }

    }

    public function targetMarketplaceProcess()
    {
        $this->attributeMappingPath = 'targets';
        $targetMarketplace = $this->process_data['target_marketplace'];

        if (isset($this->process_data['target_shop_id'])) {

            $response = $this->targetShopIdProcess($targetMarketplace, $this->profile_data['targets'][$targetMarketplace]['shops'][$this->process_data['target_shop_id']]);
        } else {
            $targetsShopsData = $this->profile_data['targets'][$targetMarketplace]['shops'];

            $response = $this->targetShopIdsProcess($targetMarketplace, $targetsShopsData);
        }
        return $response;
    }

    public function targetShopIdsProcess($targetMarketplace, $targetsShopsData)
    {
        $response = [];
        foreach ($targetsShopsData as $tagetshopId => $tagetshopIdValue) {
            if (isset($tagetshopIdValue['active']) && !$tagetshopIdValue['active']) {
                continue;
            }

            $response = $this->targetShopIdProcess($targetMarketplace, $tagetshopIdValue);
        }
        return $response;

    }

    public function targetShopIdProcess($targetMarketplace, $tagetshopIdValue)
    {

        $mappedAttributes = [];
        $prevPath = "targets.$targetMarketplace.shops";
        $this->attributeMappingPath = $prevPath . '.' . $tagetshopIdValue['shop_id'];
        // inventory impact
        $response = [];
        foreach ($tagetshopIdValue['warehouses'] as $targetWarehouseId => $targetWarehouseIdValue) {
            if (isset($targetWarehouseIdValue['active']) && !$targetWarehouseIdValue['active']) {
                continue;
            }

            $response = $this->targetWarehouseIdProcess($targetMarketplace, $targetWarehouseIdValue, $mappedAttributes);
        }
        $this->attributeMappingPath = $prevPath;

        if (!empty($mappedAttributes)) {
            $response['mappedAttributes'] = $mappedAttributes;
        }
        // print_r($response);
        if ($response['success']) {
            $productData = $this->getProductData();
            // print_r($productData);die;

            if ($productData['success']) {
                $productData = $productData['data'];
                $newProductData = [];

                if (isset($response['mappedAttributes'])) {
                    $mappedAttributes = $response['mappedAttributes'];

                    foreach ($productData as $productCol => $product) {
                        foreach ($mappedAttributes as $columnName => $mappedData) {

                            $namespace = "\\App\\Connector\\Models\\Profile\\Attribute\\Type\\" . ucfirst($mappedData['type']);
                            if (class_exists($namespace)) {
                                $obj = new $namespace();
                                $product = $obj->changeData($columnName, $mappedData, $product);
                            }

                        }

                        if (!empty($product['variants'])) {
                            foreach ($product['variants'] as $proCol => $variant) {
                                foreach ($mappedAttributes as $columnName => $mappedData) {
                                    $namespace = "\\App\\Connector\\Models\\Profile\\Attribute\\Type\\" . ucfirst($mappedData['type']);
                                    if (class_exists($namespace)) {
                                        $obj = new $namespace();
                                        $variant = $obj->changeData($columnName, $mappedData, $variant);
                                    }

                                }
                                $product['variants'][$proCol] = $variant;
                            }

                            $newProductData[] = $product;

                        } else {
                            $newProductData[] = $product;
                        }

                    }

                } else {
                    $newProductData = $this->getProductData();
                }
                $this->product_data = null;
                if (!empty($newProductData)) {
                    $connectorHelper = $this->di->getObjectManager()
                        ->get('App\Connector\Components\Connectors')
                        ->getConnectorModelByCode($targetMarketplace);
                    $connectorHelper->startUpload($newProductData, $tagetshopIdValue['shop_id'], $this->profile_data,$this->process_data);
                }
            } else {

                return $productData;
            }

            return ['success' => true, 'data' => $response];
        } else {
            return ['success' => false, 'message' => $response['message']];
        }
    }

    public function targetWarehouseIdProcess($targetMarketplace, $targetWarehouseIdValue, &$mappedAttributes)
    {
        $allSources = $targetWarehouseIdValue['sources'];
        $sourceWareHouses = [];
        $response = [];
        $prevPath = $this->attributeMappingPath;
        $this->attributeMappingPath = $prevPath . '.warehouses.' . $targetWarehouseIdValue['warehouse_id'];

        foreach ($allSources as $sourceMarketplaceId => $sourceMarketplaceIdValue) {
            $sourceWareHouses[] = $sourceMarketplaceId;
            $response = $this->sourceMarketplaceIdProcess($targetMarketplace, $sourceMarketplaceIdValue, $mappedAttributes, $sourceWareHouses);
        }
        $this->attributeMappingPath = $prevPath;

        if (empty($response)) {
            return ['success' => false, 'message' => 'No source setup'];
        } else {
            return ['success' => true, 'data' => $response];
        }

    }

    public function sourceMarketplaceIdProcess($targetMarketplace, $sourceMarketplaceIdValue, &$mappedAttributes, &$sourceWareHouses)
    {

        $response = [];
        $prevPath = $this->attributeMappingPath;
        $this->attributeMappingPath = $prevPath . '.sources.' . $sourceMarketplaceIdValue['source_marketplace_name'];

        foreach ($sourceMarketplaceIdValue['shops'] as $sourceMarketplaceShopId => $sourceMarketplaceShopIdValue) {
            if (isset($sourceMarketplaceShopIdValue['active']) && !$sourceMarketplaceShopIdValue['active']) {
                continue;
            }

            $response = $this->sourceMarketplaceShopIdProcess($targetMarketplace, $sourceMarketplaceShopIdValue, $mappedAttributes, $sourceWareHouses);

        }

        $this->attributeMappingPath = $prevPath;
        if (empty($response)) {
            return ['success' => false, 'message' => 'Source marketplace id not setuped'];
        } else {
            return ['success' => true, 'data' => $response];
        }
        return $response;
    }

    public function sourceMarketplaceShopIdProcess($targetMarketplace, $sourceMarketplaceShopIdValue, &$mappedAttributes, &$sourceWareHouses)
    {
        $response = [];
        $prevPath = $this->attributeMappingPath;
        $this->attributeMappingPath = $prevPath . '.shops.' . $sourceMarketplaceShopIdValue['shop_id'];

        foreach ($sourceMarketplaceShopIdValue['warehouses'] as $sourceMarketplaceWarehouseId => $sourceMarketplaceWarehouseIdValue) {
            if (isset($sourceMarketplaceWarehouseIdValue['active']) && !$sourceMarketplaceWarehouseIdValue['active']) {
                continue;
            }

            $sourceWareHouses[] = $sourceMarketplaceWarehouseId;
            $response = $this->sourceMarketplaceWarehouseId($targetMarketplace, $sourceMarketplaceWarehouseIdValue, $mappedAttributes, $sourceWareHouses);

        }
        $this->attributeMappingPath = $prevPath;

        return ['success' => true, 'data' => $response];
    }

    public function sourceMarketplaceWarehouseId($targetMarketplace, $sourceMarketplaceWarehouseIdValue, &$mappedAttributes, &$sourceWareHouses)
    {

        $prevPath = $this->attributeMappingPath;
        $this->attributeMappingPath = $prevPath . '.warehouses.' . $sourceMarketplaceWarehouseIdValue['warehouse_id'] . '.attributes_mapping';

        $helperDataArr = [];

        $helperDataArr['profile_data'] = $this->profile_data;
        $helperDataArr['marketplace'] = $targetMarketplace;
        $helperDataArr['source_marketplace'] = $targetMarketplace;

        $profileHelperObj = new Helper();
        $profileHelperObj->process_data = $helperDataArr;

        $attributeRes = $profileHelperObj->getProfileAttribute($this->attributeMappingPath);

        if ($attributeRes['success']) {
            $mappedAttributes = array_merge($mappedAttributes, $attributeRes['data']);
        }
        $this->attributeMappingPath = $prevPath;

    }

}
