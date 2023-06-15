<?php

namespace App\Core\Models\User;

use Exception;

class Details extends \App\Core\Models\BaseMongo
{
    protected $table = 'user_details';

    protected $isGlobal = true;

    /**
     * Set user config data by key
     *
     * @param string $key
     * @param mixed $value
     * @param int $userId
     * @return array with success true/false and message
     */
    public function setConfigByKey($key, $value, $userId = false)
    {
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();
        if ($key == 'shops') {
            if (is_array($value)) {
                foreach ($value as $ke => $val) {
                    $this->addShop($val);
                }
                return ['success' => true, 'message' => 'Shops Added Successfuly'];
            }
            return ['success' => false, 'message' => 'Shops Data format not correct', 'code' => 'wrong_format'];
        } else {
            $app_tag = $this->di->getAppCode()->getAppTag();
            $setting_value = [
                'value' => $value,
                'updated_at' => date('c')
            ];
            $result = $collection->UpdateOne(
                ['user_id' => $userId],
                ['$set' => ["{$app_tag}.{$key}" => $setting_value]]
            );
            if ($result->getMatchedCount()) {
                return ['success' => true, 'message' => 'Shop Updated Successfuly'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched 1', 'code' => 'no_record_found'];
            }
        }
    }

    /**
     * Get user config data by key
     *
     * @param string $key
     * @param int $userId
     * @return mixed
     */
    public function getConfigByKey($key, $userId = false)
    {
        try {
            if (!$userId) {
                $userId = $this->di->getUser()->id;
            }

            $app_tag = $this->di->getAppCode()->getAppTag();
            $collection = $this->getCollection();
            $result = $this->loadByField(
                ['_id' => $userId],
                [
                    "typeMap" => ['root' => 'array', 'document' => 'array'],
                    "projection" => ["{$app_tag}.{$key}" => 1],
                ]
            );
            if (!empty($result) && isset($result[$app_tag][$key])) {
                return $result[$app_tag][$key]['value'] ?? $result[$app_tag][$key];
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user config data
     *
     * @param int $userId
     * @return array
     */
    public function getConfig($userId = false)
    {
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();
        return $collection->findOne(
            ['user_id' => $userId],
            ["typeMap" => ['root' => 'array', 'document' => 'array']]
        );
    }

    /**
     * Get shop details of user
     *
     * @param int $shopId
     * @param int $userId
     * @return array
     */
    public function getShop($shopId, $userId = false)
    {
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        if (!$shopId) {
            return false;
        }
        $collection = $this->getCollection();
        $result = $collection->findOne(
            ['user_id' => (string)$userId, 'shops._id' => (string)$shopId],
            [
                "projection" => ['shops.$' => 1],
                "typeMap" => ['root' => 'array', 'document' => 'array'],
            ]
        );
        if (!empty($result)) {
            return $result['shops'][0];
        }
        return false;
    }

    /**
     * Find shop details of user
     *
     * @param int $shopId
     * @param int $userId
     * @return array
     */
    public function findShop($data, $userId = false)
    {
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $filters = [];
        $filters['user_id'] = $userId;

        foreach ($data as $key => $value) {
            $filters['shops.' . $key] = $value;
        }

        $collection = $this->getCollection();
        $result = $collection->findOne(
            $filters,
            [
                "typeMap" => ['root' => 'array', 'document' => 'array'],
                "projection" => ['shops.$' => 1],
            ]
        );
        return $result['shops'];
    }

    /**
     * Get warehouse details of specific shop
     *
     * @param int $shopId
     * @param int $warehouseId
     * @param int $userId
     * @return array
     */
    public function getWarehouse($shopId, $warehouseId, $userId = false)
    {
        // print_r($shopId,$warehouseId);die("djhjsd");

        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }

        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }

        if (!$shopId || !$warehouseId) {
            return false;
        }
        $filters = ['_id' => $userId, 'shops._id' => $shopId, 'shops.warehouses._id' => $warehouseId];
        $finalQuery = [
            ['$match' => $filters],
            ['$unwind' => '$shops'],
            ['$unwind' => '$shops.warehouses'],
            ['$match' => $filters],
            ['$project' => ['shops.warehouses' => 1]],
        ];
        $collection = $this->getCollection();
        $result = $collection->aggregate(
            $finalQuery,
            ['typeMap' => ['root' => 'array', 'document' => 'array']]
        )->toArray();
        if (!empty($response)) {
            return $result[0]['shops']['warehouses'];
        }
        return false;
    }

    public function getWarehouseMarketplaceWise($marketplace, $user_id = false)
    {
        if (!$user_id) {
            $user_id = $this->di->getUser()->id;
        }


        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }

        $collection = $this->getCollection();
        $user_details = $collection->findOne(['_id' => $user_id]);

        if (empty($user_details)) {
            return ['success' => false, 'message' => 'Shop details not found'];
        }
        $warehouses = [];

        if ($marketplace) {
            foreach ($user_details['shops'] as $value) {
                if ($value['marketplace'] === $marketplace) {
                    if (isset($value['warehouses'])) {
                        foreach ($value['warehouses'] as $warehouse) {
                            $warehouses[] = [
                                'id' => $warehouse['_id'],
                                'name' => $warehouse['name'] ?? $warehouse['_id']
                            ];
                        }
                    }
                }
            }
        }
        return $warehouses;
    }

    /**
     * Add Shop in user_details table
     *
     * @param array $shopDetails ['name'=>'','domain'=>'','marketplace'=>'','warehouses'=>[['name'=>'','location'=>'']]]
     * @param int $userId
     * @param array $uniqueKeys ['domain', '_id']
     * @return array with success true/false and message
     */
    public function addShop($shopDetails, $userId = false, $uniqueKeys = ['domain'])
    {
        if (empty($shopDetails)) {
            return ['success' => false, 'message' => 'Shop details not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }

        $collection = $this->getCollection();
        $filters = [];
        $filters['_id'] = $userId;
        foreach ($uniqueKeys as $key) {
            if (isset($shopDetails[$key])) {
                $filters["shops.{$key}"] = $shopDetails[$key];
            }
        }
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => ['shops' => 1],
        ];

        try {
            $response = $collection->findOne($filters, $options);
            if (!empty($response)) {
                $index = 0;
                foreach ($response['shops'] as $keys => $shopValue) {
                    //                    $flag = true;
                    //                    foreach ($uniqueKeys as $key) if (!isset($shopValue[$key])) $flag = false;
                    //                    if ( $flag ) $index = $keys;
                    foreach ($uniqueKeys as $key) {
                        if (isset($shopValue[$key]) && $shopValue[$key] === $shopDetails[$key]) {
                            $index = $keys;
                        }
                    }

                }
                $foundShop = $response['shops'][$index];
                $finalShop = $this->mergeShopData($foundShop, $shopDetails);
                //$finalShop['updated_at'] = date('c');
                $finalShop['updated_at'] = new \MongoDB\BSON\UTCDateTime();

                $updateResult = $collection->updateOne(
                    ['_id' => $userId, 'shops' => ['$elemMatch' => ['_id' => $finalShop['_id']]]],
                    ['$set' => ['shops.$' => $finalShop]]
                );
                if ($updateResult->getMatchedCount()) {
                    $this->setUserCache('shops', false);
                    return ['success' => true,
                        'message' => 'Shop Updated Successfuly',
                        'data' => [
                            'shop_id' => $finalShop['_id'],
                        ],
                    ];
                } else {
                    return ['success' => false, 'message' => 'Shop Updated Successfuly', 'finalShop' => $finalShop];
                }
            } else {
                $shopDetails['_id'] = $this->getCounter('shop_id');
                $shopDetails['created_at'] = new \MongoDB\BSON\UTCDateTime();
                $shopDetails['updated_at'] = new \MongoDB\BSON\UTCDateTime();
                if (isset($shopDetails['warehouses'])) {
                    foreach ($shopDetails['warehouses'] as $key => $warehouse) {
                        if (isset($shopDetails['marketplace']) && in_array($shopDetails['marketplace'], ['ebay', 'amazon'])) {
                            $shopDetails['warehouses'][$key]['_id'] = $shopDetails['_id'];
                        } else {
                            $shopDetails['warehouses'][$key]['_id'] = $this->getCounter('warehouse_id');
                        }
                    }
                } else {
                    $shopDetails['warehouses'] = [];
                }

                $updateResult = $collection->updateOne(
                    ['_id' => $userId],
                    ['$push' => ['shops' => $shopDetails]]
                );
                $this->setUserCache('shops', false);
                return ['success' => true,
                    'message' => 'Shop Added Successfuly',
                    'data' => [
                        'shop_id' => $shopDetails['_id'],
                    ],
                ];
                /*if ($updateResult->getMatchedCount()) {
                    return ['success' => true,
                        'message' => 'Shop Added Successfuly',
                        'data' => [
                            'shop_id' => $shopDetails['_id'],
                        ],
                    ];
                } else {
                    return ['success' => false, 'message' => 'No Record Matched 2', 'code' => 'no_record_found'];
                }*/
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }

    /**
     * Merge Shop data including the warehoue. It wll be used in add/update Shop functions.
     *
     * @param array $shopDetails
     * @param array $uniqueKeys
     * @return array with merge shop data
     */
    private function mergeShopData($foundShop, $shopDetails)
    {
        $warehouses = [];
        if (isset($shopDetails['warehouses'])) {
            if (isset($foundShop['warehouses']) && !empty($foundShop['warehouses'])) {
                foreach ($foundShop['warehouses'] as $key => $warehouse) {
                    $warehouses[$foundShop['warehouses'][$key]['_id']] = $foundShop['warehouses'][$key];
                }
            }
            foreach ($shopDetails['warehouses'] as $key => $warehouse) {
                if (!isset($shopDetails['warehouses'][$key]['_id'])) {
                    if (isset($shopDetails['marketplace']) && in_array($shopDetails['marketplace'], ['ebay', 'amazon'])) {
                        $shopDetails['warehouses'][$key]['_id'] = $foundShop['_id'];
                    } else {
                        $shopDetails['warehouses'][$key]['_id'] = $this->getCounter('warehouse_id');
                    }
                } else {
                    $shopDetails['warehouses'][$key] = array_merge($warehouses[$shopDetails['warehouses'][$key]['_id']], $shopDetails['warehouses'][$key]);
                }
            }
        } else if (isset($foundShop['warehouses'])) {
            $shopDetails['warehouses'] = $foundShop['warehouses'];
        }
        //        return array_merge($shopDetails, $foundShop);
        return array_merge($foundShop, $shopDetails);
    }

    /**
     * Update Shop in user_details table
     *
     * @param array $shopDetails ['name' = '', 'domain' => '', 'warehouses' => [ ['name'= > '', 'location' => '']]]
     * @param int $userId
     * @param array $uniqueKeys ['domain', '_id']
     * @return array with success true/false and message
     */
    public function updateShop($shopDetails, $userId = false, $uniqueKeys = ['domain'])
    {
        if (empty($shopDetails)) {
            return ['success' => false, 'message' => 'Shop details not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }

        if (is_string($userId)) {
            $userId = new \MongoDB\BSON\ObjectId($userId);
        }
        $collection = $this->getCollection();
        $filters = [];
        $filters['_id'] = $userId;

        foreach ($uniqueKeys as $key) {
            if (isset($shopDetails[$key])) {
                $filters["shops.{$key}"] = $shopDetails[$key];
            }
        }
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => ['shops.$' => 1],
        ];

        try {
            $response = $collection->find($filters, $options)->toArray();
            if (!empty($response)) {
                $foundShop = $response[0]['shops'][0];
                $finalShop = $this->mergeShopData($foundShop, $shopDetails);
                $finalShop['updated_at'] = date('c');
                $updateResult = $collection->updateOne(
                    ['_id' => $userId, 'shops' => ['$elemMatch' => ['_id' => $finalShop['_id']]]],
                    ['$set' => ['shops.$' => $finalShop]]
                );
                if ($updateResult->getMatchedCount()) {
                    $this->setUserCache('shops', false);
                    return ['success' => true, 'message' => 'Shop Updated Successfuly'];
                } else {
                    return ['success' => false, 'message' => 'No Record Matched 3', 'code' => 'no_record_found', 'finalData' => $finalShop];
                }
            } else {
                return ['success' => false, 'message' => 'No shop found', 'code' => 'not_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }

    /**
     * Delete Shop in user_details table
     *
     * @param int $shopId
     * @param int $userId
     * @return array with success true/false and message
     */
    public function deleteShop($shopId, $userId = false)
    {
        if (!$shopId) {
            return ['success' => false, 'message' => 'Shop Id not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }

        $collection = $this->getCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        $filters['shops._id'] = $shopId;
        try {
            $updateResult = $collection->updateOne(
                $filters,
                ['$pull' => ['shops' => ['_id' => $shopId]]]
            );
            if ($updateResult->getMatchedCount()) {
                $this->setUserCache('shops', false);
                return ['success' => true, 'message' => 'Shop has been Deleted Successfuly'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched 4', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }

    /**
     * Add Warehouse in user_details table
     *
     * @param int $shopId
     * @param array $warehouseDetails ['name'=> '', 'location' => '']
     * @param int $userId
     * @param array $uniqueKeys ['_id']
     * @return array with success true/false and message
     */
    public function addWarehouse($shopId, $warehouseDetails, $userId = false, $uniqueKeys = ['_id'])
    {

        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        $filters['shops._id'] = $shopId;
        if (!$shopId || empty($warehouseDetails)) {
            return ['success' => false, 'message' => 'Shop Id or Warehouse details not provided', 'code' => 'insuficiant_data'];
        }
        foreach ($uniqueKeys as $key) {
            if (isset($warehouseDetails[$key])) {
                $filters["shops.warehouses.{$key}"] = $warehouseDetails[$key];
            }
        }

        $finalQuery = [
            ['$match' => $filters],
            ['$unwind' => '$shops'],
            ['$match' => $filters],
            ['$project' => ['shops.warehouses' => 1]],
        ];

        try {
            $response = $collection->aggregate(
                $finalQuery,
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            )->toArray();

            if (empty($response) || (isset($response[0]['shops']['warehouses']) && empty(($response[0]['shops']['warehouses'])))) {
                $warehouseDetails['_id'] = $this->getCounter('warehouse_id');
                $updateResult = $collection->updateOne(
                    ['user_id' => $userId, 'shops' => ['$elemMatch' => ['_id' => $shopId]]],
                    ['$push' => ['shops.$.warehouses' => $warehouseDetails]]
                );
                if ($updateResult->getMatchedCount()) {
                    return ['success' => true, 'message' => 'Warehouse has been added Successfuly in Shop'];
                } else {
                    return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
                }
            } else {
                $updateWarehouse = $this->updateWarehouse($shopId, $warehouseDetails, $userId, $uniqueKeys);
                return $updateWarehouse;
                /* return ['success' => false, 'message' => 'Warehouse already exists in Shop', 'code' => 'already_exists']; */
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception', 'msg_ss' => $e->getMessage(), 'msg' => $e->getMessage()];
        }
    }

    /**
     * Add Warehouse in user_details table
     *
     * @param int $shopId
     * @param array $warehouseDetails ['_id' => 'name' => '', 'location' => '']
     * @param int $userId
     * @param array $uniqueKeys ['_id']
     * @return array with success true/false and message
     */
    public function updateWarehouse($shopId, $warehouseDetails, $userId = false, $uniqueKeys = ['_id'])
    {
        if (!$shopId || empty($warehouseDetails)) {
            return ['success' => false, 'message' => 'Shop Id or Warehouse details not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getPhpCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        $filters['shops._id'] = $shopId;
        $arraFilter = [];
        $update = [];
        foreach ($uniqueKeys as $key) {
            if (isset($warehouseDetails[$key])) {
                $filters["shops.warehouses.{$key}"] = $warehouseDetails[$key];
                $arraFilter[] = ["element.{$key}" => $warehouseDetails[$key]];
            }
        }
        foreach ($warehouseDetails as $ke => $value) {
            $update["shops.$[].warehouses.$[element].{$ke}"] = $value;
        }
        try {
            $updateResult = $collection->updateOne($filters, ['$set' => $update], ['arrayFilters' => $arraFilter]);
            if ($updateResult->getMatchedCount()) {
                return ['success' => false, 'message' => 'Warehouse has been Updated Successfuly in Shop'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched 7', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Delete warehouse in user_details table
     *
     * @param int $shopId
     * @param int $warehouseId
     * @param bool $userId
     * @return array with success true/false and message
     */
    public function deleteWarehouse($shopId, $warehouseId, $userId = false)
    {
        if (!$shopId || !$warehouseId) {
            return ['success' => false, 'message' => 'Shop Id or Warehouse Id not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getPhpCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        $filters['shops._id'] = $shopId;
        $filters["shops.warehouses._id"] = $warehouseId;

        try {
            $updateResult = $collection->updateOne(
                $filters,
                ['$pull' => ['shops.$.warehouses' => ['_id' => $warehouseId]]]
            );
            if ($updateResult->getMatchedCount()) {
                return ['success' => true, 'message' => 'Warehouse has been Deleted Successfuly from Shop'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched 8', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception', 'msg' => $e->getMessage()];
        }
    }

    public function getUserbyShopId($shopId, $projectionData = ['user_id' => 1])
    {
        $collection = $this->getCollection();
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => $projectionData,
        ];
        $filters = [
            'shops.remote_shop_id' => $shopId,
        ];
        try {
            $response = $collection->findOne($filters, $options);
            return $response;
        } catch (Exception $e) {
            // log $e->message internally
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception', 'msg' => $e->getMessage()];
        }
    }

    /**
     * @param bool $user_id
     * @param bool $marketplace
     * @return array
     */
    public function getDataByUserID($user_id = false, $marketplace = false, $warehouse_id = false)
    {
        if (!$user_id) {
            $user_id = $this->di->getUser()->id;
        }

        $collection = $this->getCollection();
        $user_details = $collection->findOne(['user_id' => (string)$user_id]);

        if (empty($user_details)) {
            return ['success' => false, 'message' => 'Shop details not found'];
        }
        $shops = [];

        if ($marketplace) {
            foreach ($user_details['shops'] as $value) {
                if ($value['marketplace'] === $marketplace) {
                    if ($warehouse_id) {
                        foreach ($value['warehouses'] as $warehouse) {
                            if ($warehouse['_id'] == $warehouse_id) {
                                $shops = $value;
                            }
                        }
                    } else {
                        $shops = $value;
                    }
                }
            }
        }
        return $shops;
    }

    /**
     * Get user details for given key
     *
     * @param string $key
     * @param int $userId
     * @return mixed
     */
    public function getUserDetailsByKey($key, $userId = false)
    {
        try {
            if (!$userId) {
                $userId = $this->di->getUser()->id;
            }

            $app_tag = $this->di->getAppCode()->getAppTag();
            $collection = $this->getCollection();
            $result = $this->loadByField(
                ['_id' => $userId],
                [
                    "typeMap" => ['root' => 'array', 'document' => 'array'],
                    "projection" => ["{$key}" => 1],
                ]
            );
            if (!empty($result) && isset($result[$key])) {
                return $result[$key];
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function getUserByRemoteShopId($shopId, $projectionData = ['user_id' => 1], $appCode = false)
    {
        $collection = $this->getCollection();
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => $projectionData,
        ];
        $filters = [
            'shops.remote_shop_id' => $shopId,
        ];
        if ($appCode) {
            $filters['shops.apps.code'] = $appCode;
        }


        try {
            $response = $collection->findOne($filters, $options);
            return $response;
        } catch (Exception $e) {
            // log $e->message internally
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception', 'msg' => $e->getMessage()];
        }
    }

    /**
     * @param $user_id
     * @param bool $marketplace
     * @param bool $shop_id
     * @param bool $warehouse_id
     * @return array
     */
    public function getAllConnectedShops($user_id, $marketplace = false, $shop_id = false, $warehouse_id = false)
    {
        if (!$user_id) {
            $user_id = $this->di->getUser()->id;
        }

        $collection = $this->getCollection();
        $user_details = $collection->findOne(['user_id' => (string)$user_id]);

        if (empty($user_details)) {
            return ['success' => false, 'message' => 'Shop details not found'];
        }
        $shops = [];

        if ($marketplace) {
            foreach ($user_details['shops'] as $value) {
                if ($value['marketplace'] === $marketplace) {
                    if ($warehouse_id) {
                        foreach ($value['warehouses'] as $warehouse) {
                            if ($warehouse['_id'] == $warehouse_id) {
                                $shops[] = $value;
                            }
                        }
                    } else {
                        $shops[] = $value;
                    }
                }
            }
        }
        return $shops;
    }
}
