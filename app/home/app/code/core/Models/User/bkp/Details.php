<?php
namespace App\Core\Models\User;

use App\Core\Models\Base;
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
    public function setConfigByKey($key, $value, $userId = false){
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
            $result = $collection->UpdateOne(
                ['user_id' => $userId],
                ['$set' => ["{$key}" => $value]]
            );
            if ($result->getMatchedCount()) {
                return ['success' => true, 'message' => 'Shop Updated Successfuly'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
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
    public function getConfigByKey($key, $userId = false){
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();
        $result = $collection->findOne(
            ['user_id' => $userId],
            [
                "typeMap" => ['root' => 'array', 'document' => 'array'],
                "projection" => ["{$key}" => 1]
            ]
        );
        if (!empty($result) && isset($result[$key])) {
            return $result[$key];
        }
        return false;
    }

    /**
     * Get user config data
     *
     * @param int $userId
     * @return array
     */
    public function getConfig($userId = false){
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
    public function getShop($shopId, $userId = false){
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        if (!$shopId) {
            return false;
        } 
        $collection = $this->getCollection();
        $result = $collection->findOne(
                    ['user_id' => $userId, 'shops._id' => $shopId],
                    [
                        "typeMap" => ['root' => 'array', 'document' => 'array'],
                        "projection" => ['shops.$' => 1]
                    ]
                );
        if (!empty($response)) {
            return $result['shops'][0];
        }
        return false;
    }

    /**
     * Get warehouse details of specific shop
     *
     * @param int $shopId
     * @param int $warehouseId
     * @param int $userId
     * @return array
     */
    public function getWarehouse($shopId, $warehouseId, $userId = false){
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        if (!$shopId || !$warehouseId) {
            return false;
        }
        $filters = ['user_id' => $userId, 'shops._id' => $shopId, 'shops.warehouses._id' => $warehouseId];
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

    /**
     * Add Shop in user_details table
     *
     * @param array $shopDetails ['name'=>'','domain'=>'','marketplace'=>'','warehouses'=>[['name'=>'','location'=>'']]]
     * @param int $userId
     * @param array $uniqueKeys ['domain', '_id']
     * @return array with success true/false and message
     */
    public function addShop($shopDetails, $userId = false, $uniqueKeys = ['domain']){
        if (empty($shopDetails)) {
            return ['success' => false, 'message' => 'Shop details not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;            
        }

        $collection = $this->getCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        foreach ($uniqueKeys as $key) {
            if (isset($shopDetails[$key])) {
                $filters["shops.{$key}"] = $shopDetails[$key];
            }
        }
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => ['shops' => 1]
        ];

        try {
            $response = $collection->findOne($filters, $options);
            if (!empty($response)) {
                $index = 0;
                foreach ( $response['shops'] as $keys => $shopValue ) {
                    $flag = true;
                    foreach ($uniqueKeys as $key) if (!isset($shopValue[$key])) $flag = false;
                    if ( $flag ) $index = $keys;
                }
                $foundShop = $response['shops'][$index];
                $finalShop = $this->mergeShopData($foundShop, $shopDetails);
                $updateResult = $collection->updateOne(
                    ['user_id' => $userId, 'shops' => ['$elemMatch' => ['_id' => $finalShop['_id']]]],
                    ['$set' => ['shops.$' => $finalShop]]
                );
                if ($updateResult->getMatchedCount()) {
                    return ['success' => true,
                            'message' => 'Shop Updated Successfuly',
                            'data'=>[
                                'shop_id'=>$finalShop['_id']
                            ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Shop Updated Successfuly'];
                }
            } else {
                if (isset($shopDetails['warehouses'])) {
                    foreach ($shopDetails['warehouses'] as $key => $warehouse) {
                        $shopDetails['warehouses'][$key]['_id'] = $this->getCounter('warehouse_id');
                    }
                } else {
                    $shopDetails['warehouses'] = [];
                }
                $shopDetails['_id'] = $this->getCounter('shop_id');   

                $updateResult = $collection->updateOne(
                    ['user_id' => (string)$userId],
                    ['$push' => ['shops' => $shopDetails]]
                );                                              
                if ($updateResult->getMatchedCount()) {
                    return ['success' => true,
                            'message' => 'Shop Added Successfuly',
                            'data'=>[
                                'shop_id'=>$shopDetails['_id']
                            ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
                }
            }
        } catch (\Exception $e){
            echo $e->getMessage();die;
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
    private function mergeShopData($foundShop, $shopDetails){
        $warehouses = [];
        if (isset($shopDetails['warehouses'])) {
            if (isset($foundShop['warehouses']) && !empty($foundShop['warehouses'])) {
                foreach ($foundShop['warehouses'] as $key => $warehouse) {
                    $warehouses[$foundShop['warehouses'][$key]['_id']] = $foundShop['warehouses'][$key];
                }    
            }
            foreach ($shopDetails['warehouses'] as $key => $warehouse) {
                if (!isset($shopDetails['warehouses'][$key]['_id'])) {
                    $shopDetails['warehouses'][$key]['_id'] = $this->getCounter('warehouse_id');
                } else {
                    $shopDetails['warehouses'][$key] = array_merge($warehouses[$shopDetails['warehouses'][$key]['_id']], $shopDetails['warehouses'][$key]);        
                }
            }
        }/* else {
//            $shopDetails['warehouses'] = [];
        }*/
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
    public function updateShop($shopDetails, $userId = false, $uniqueKeys = ['domain']){
        if (empty($shopDetails)) {
            return ['success' => false, 'message' => 'Shop details not provided', 'code' => 'insuficiant_data'];
        }
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();
        $filters = [];
        $filters['user_id'] = $userId;
        
        foreach ($uniqueKeys as $key) {
            if (isset($shopDetails[$key])) {
                $filters["shops.{$key}"] = $shopDetails[$key];
            }
        }
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => ['shops.$' => 1]
        ];

        try {
            $response = $collection->find($filters, $options)->toArray();
            if (!empty($response)) {
                $foundShop = $response[0]['shops'][0];
                $finalShop = $this->mergeShopData($foundShop, $shopDetails);
                $updateResult = $collection->updateOne(
                    ['user_id' => $userId, 'shops' => ['$elemMatch' => ['_id' => $finalShop['_id']]]],
                    ['$set' => ['shops.$' => $finalShop]]
                );
                if ($updateResult->getMatchedCount()) {
                    return ['success' => true, 'message' => 'Shop Updated Successfuly'];
                } else {
                    return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
                }
            } else {
                return ['success' => false, 'message' => 'No shop found', 'code' => 'not_found'];
            }
        } catch (\Exception $e){
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
    public function deleteShop($shopId, $userId = false){
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
                ['$pull' => ['shops'  => ['_id' => $shopId]]]
            );
            if ($updateResult->getMatchedCount()) {
                return ['success' => true, 'message' => 'Shop has been Deleted Successfuly'];
            } else {
                return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e){
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }

    /**
     * Add Warehouse in user_details table
     *
     * @param int $shopId
     * @param array $warehouseDetails  ['name'=> '', 'location' => '']
     * @param int $userId
     * @param array $uniqueKeys ['_id']
     * @return array with success true/false and message
     */
    public function addWarehouse($shopId, $warehouseDetails, $userId = false, $uniqueKeys = ['_id']){

        /* echo "shop =".$shopId;
        print_r($warehouseDetails); */

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
            ['$project' => ['shops.warehouses' => 1] ],
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
                $updateWarehouse = $this->updateWarehouse($shopId, $warehouseDetails, $userId);
                return $updateWarehouse;
                /* return ['success' => false, 'message' => 'Warehouse already exists in Shop', 'code' => 'already_exists']; */
            }
        } catch (\Exception $e){
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        } 
    }

    /**
     * Add Warehouse in user_details table
     *
     * @param int $shopId
     * @param array $warehouseDetails  ['_id' => 'name' => '', 'location' => '']
     * @param int $userId
     * @param array $uniqueKeys ['_id']
     * @return array with success true/false and message
     */
    public function updateWarehouse($shopId, $warehouseDetails, $userId = false, $uniqueKeys = ['_id']){
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
                $arraFilter[] = ["element.'{$key}" => $warehouseDetails[$key]];
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
                return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
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
    public function deleteWarehouse($shopId, $warehouseId, $userId = false){
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
                return ['success' => false, 'message' => 'No Record Matched', 'code' => 'no_record_found'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }

    public function getUserbyShopId($shopId, $projectionData = ['user_id' => 1]){
        $collection = $this->getCollection();
        $options = [
            "typeMap" => ['root' => 'array', 'document' => 'array'],
            "projection" => $projectionData
        ];
        $filters = [
            'shops.remote_shop_id' => $shopId
        ];
        try {
            $response = $collection->findOne($filters, $options);
            return $response;            
        } catch (Exception $e){
            // log $e->message internally
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'unknown_exception'];
        }
    }


    /**
     * @param bool $user_id
     * @param bool $marketplace
     * @return array
     */
    public function getDataByUserID($user_id = false, $marketplace = false)
    {
        if ( !$user_id ) $user_id = $this->di->getUser()->id;
        $collection = $this->getCollection();
        $user_details = $collection->findOne(['user_id' => (string)$user_id]);

        if ( empty($user_details) ) return ['success' => false, 'message' => 'Shop details not found'];

        if ( $marketplace ) {
            foreach ( $user_details['shops'] as $value ) {
                if ( $value['marketplace'] === $marketplace ) {
                    return $value;
                }
            }
        }
        return $user_details;
    }
}