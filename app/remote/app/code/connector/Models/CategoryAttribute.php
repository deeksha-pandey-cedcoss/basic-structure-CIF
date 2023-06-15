<?php

namespace App\Connector\Models;

use App\Core\Models\BaseMongo;

class CategoryAttribute extends BaseMongo
{
    protected $table = 'category_attributes';
    protected $marketplace;
    protected $categoryIdVal;
    protected $isGlobal = true;


    public function getAllAttribute($request)
    {
        if (isset($request['marketplace'])) {
            $this->marketplace = $request['marketplace'];
            if (isset($request['category_id'])) {
                $this->categoryIdVal = $request['category_id'];
            }
        } else {
            return ['success' => false, 'code' => 'undefined_marketplace', 'message' => 'Marketplace is not defined'];
        }

        $globalAttributes = $this->getGlobalAttribute();
        if (!is_null($this->categoryIdVal)) {
            $categoryAttribute = $this->getCategoryWiseAttr();
            $allData = array_merge($globalAttributes['data'], $categoryAttribute['data']);
            return ['success' => true, 'message' => '', 'data' => $allData];
        } else {
            return ['success' => true, 'message' => '', 'data' => $globalAttributes['data']];
        }
    }

    public function getGlobalAttribute()
    {
        $marketplaceGlobalAttributes = $this->findByField(['category_id' => 0, 'marketplace' => $this->marketplace], ['sort' => ['sort_order' => 1]]);


        if (
            $marketplaceGlobalAttributes &&
            count($marketplaceGlobalAttributes) > 0
        ) {
            return ['success' => true, 'message' => '', 'data' => $marketplaceGlobalAttributes];
        } else {
            return ['success' => true, 'message' => '', 'data' => []];
        }
    }

    public function getCategoryWiseAttr()
    {

        $marketplaceCatAttributes = $this->findByField(['category_id' => $this->categoryIdVal, 'marketplace' => $this->marketplace], ['sort' => ['sort_order' => 1]]);

        if (
            $marketplaceCatAttributes &&
            count($marketplaceCatAttributes) > 0
        ) {
            return ['success' => true, 'message' => '', 'data' => $marketplaceCatAttributes];
        } else {
            return ['success' => true, 'message' => '', 'data' => []];
        }
    }

    public function createCategoryAttribute($data)
    {

        $savedData = 0;
        $notSavedData = 0;
        $bulkOpArray = [];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $mongo->setSource("category_attributes");
        $mongoCollection = $mongo->getPhpCollection();

        foreach ($data as $key => $value) {

            if (isset($value['marketplace'], $value['marketplace_attribute_id'], $value['category_id'])) {
                $exists = $this->loadByField([
                    "marketplace" => $value['marketplace'],
                    "marketplace_attribute_id" => $value['marketplace_attribute_id'],
                    "category_id" => $value['category_id']
                ]);
                $obj = $this->di->getObjectManager()->create('\App\Connector\Models\CategoryAttribute');
                unset($obj->categoryIdVal);
                unset($obj->marketplace);


                if ($exists) {
                    if (isset($value['mapping'], $exists['mapping'])) {
                        $exists['mapping'] = (array)$exists['mapping'];
                        $value['mapping'] = array_merge($value['mapping'], $exists['mapping']);
                    } elseif (isset($exists['mapping'])) {
                        $exists['mapping'] = (array)$exists['mapping'];
                        $value['mapping'] = $exists['mapping'];
                    }
                    /*$value['_id'] = $exists['_id'];*/
                
                    $bulkOpArray[] = [
                        'updateOne' => [
                            ['_id' => $exists['_id']],
                            ['$set' => $value]
                        ]
                    ];
                } else {
                    // print_r($value);die();
                    $bulkOpArray[] = [
                        'insertOne' => [
                           $value
                        ]
                    ];
                }
                /* $obj->setData($value);
                    if($obj->save()){
                        $savedData++;
                    } else {
                        $notSavedData++;
                    }*/
            }
        }
        if (!empty($bulkOpArray)) {
            $bulkObj = $mongoCollection->BulkWrite($bulkOpArray, ['w' => 1]);
            $returenRes = [
                'acknowledged' => $bulkObj->isAcknowledged(),
                'inserted' => $bulkObj->getInsertedCount(),
                'modified' => $bulkObj->getModifiedCount(),
                'matched' => $bulkObj->getMatchedCount()
            ];
            return [
                'success' => true,
                'stats' => $returenRes
            ];
        } else {
            return ['success' => false, 'message' => 'no data found'];
        }
        return ['success' => true, 'message' => 'data inserted successfully', 'data' => ['savedData' => $savedData, 'notSavedData' => $notSavedData]];
    }

    public function deleteAttribute($data)
    {
        $deleteData = 0;
        $notDeleteData = 0;
        foreach ($data as $key => $value) {
            if (isset($value['marketplace'], $value['marketplace_attribute_id'], $value['category_id'])) {
                $collection = $this->getCollection();
                $collection->deleteOne([
                    "marketplace" => $value['marketplace'],
                    "marketplace_attribute_id" => $value['marketplace_attribute_id'],
                    "category_id" => $value['category_id']
                ], ['w' => true]);
                $deleteData++;
            } else {
                $notDeleteData++;
            }
        }
        return ['success' => true, 'message' => 'data deleted successfully', 'data' => ['deleteData' => $deleteData, 'notDeleteData' => $notDeleteData]];
    }
}
