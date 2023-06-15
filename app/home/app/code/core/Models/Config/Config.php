<?php
namespace App\Core\Models\Config;

use Exception;

/**
  {
        "user_id": "\"all\" | \"user_id\"",
        "key": "string",
        "group_code": "string",
        "value" : "string",
        "app_tag": "string",
        "source" : "string",
        "target" : "string",
        "source_shop_id" : "string",
        "target_shop_id" : "string",
        "source_warehouse_id" : "string",
        "target_warehouse_id" : "string",
        "updated_at" : "timestamp",
        "created_at" : "timestamp"
    }
 */

class Config extends \App\Core\Models\Base
{
    protected $table = 'config';

    protected $isGlobal = true;

    private $user_id = '';

    private $source = null;
    private $source_shop_id = null;
    private $source_warehouse_id = null;

    private $target = null;
    private $target_shop_id = null;
    private $target_warehouse_id = null;

    private $app_tag = 'default';

    private $group_code = null;

    private $mongo = null;

    public function onConstruct()
    {
        $this->reset();
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $this->mongo = $mongo->getCollectionForTable($this->table);
    }

    public function reset()
    {
        $this->user_id = 'all';
        $this->source = null;
        $this->source_shop_id = null;
        $this->source_warehouse_id = null;
        $this->group_code = null;
        $this->target = null;
        $this->target_shop_id = null;
        $this->target_warehouse_id = null;
        $this->app_tag = $this->di->getAppCode()->getAppTag() ?? 'default';
    }

    public function setGroupCode($group_code)
    {
        $this->group_code = $group_code;
    }

    public function setUserId($user_id = null)
    {
        if ( !isset($user_id) ) {
            $this->user_id = $this->di->getUser()->id;
        } else {
            $this->user_id = $user_id;
        }
    }

    public function sourceSet($source)
    {
        $this->source = $source;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function setAppTag($app_tag)
    {
        $this->app_tag = $app_tag;
    }

    public function setSourceShopId($shop_id = null)
    {
        if ( !isset($shop_id) ) return ['success' => false, 'message' => 'Shop Id is empty'];

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $data = $collection->findOne(['shops._id' => $shop_id]);
        
        if ( isset($data['shops']) && count($data['shops']) > 0 ) {
            foreach( $data['shops'] as $shop ) {
                if ( $shop['_id'] == $shop_id ) {
                    $this->source = $shop['marketplace'] ?? 'UNKNOW';
                    $this->source_shop_id = $shop['_id'];
                }
            }
        }
    }

    public function setTargetShopId($shop_id = null)
    {
        if ( !isset($shop_id) ) return ['success' => false, 'message' => 'Shop Id is empty'];

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $data = $collection->findOne(['shops._id' => $shop_id]);

        if ( isset($data['shops']) && count($data['shops']) > 0 ) {
            foreach( $data['shops'] as $shop ) {
                if ( $shop['_id'] == $shop_id ) {
                    $this->target = $shop['marketplace'] ?? 'UNKNOW';
                    $this->target_shop_id = $shop['_id'];
                }
            }
        }
    }

    public function setSourceWarehouseId($warehouse_id = null)
    {
        if ( !isset($warehouse_id) ) return ['success' => false, 'message' => 'warehouse_id Id is empty'];

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $data = $collection->findOne(['shops.warehouses._id' => $warehouse_id]);

        if ( isset($data['shops']) && count($data['shops']) > 0 ) {
            foreach( $data['shops'] as $shop ) {
                if ( isset($shop['warehouses']) && count($shop['warehouses']) > 0 ) {
                    foreach( $shop['warehouses'] as $warehouse ) {
                        if ( $warehouse['_id'] == $warehouse_id ) {
                            $this->source = $shop['marketplace'] ?? 'UNKNOW';
                            $this->source_shop_id = $shop['_id'];
                            $this->source_warehouse_id = $warehouse['_id'];
                        }
                    }
                }
            }
        }
    }

    public function setTargetWarehouseId($warehouse_id = null)
    {
        if ( !isset($warehouse_id) ) return ['success' => false, 'message' => 'warehouse_id Id is empty'];

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $data = $collection->findOne(['shops.warehouses._id' => $warehouse_id]);

        if ( isset($data['shops']) && count($data['shops']) > 0 ) {
            foreach( $data['shops'] as $shop ) {
                if ( isset($shop['warehouses']) && count($shop['warehouses']) > 0 ) {
                    foreach( $shop['warehouses'] as $warehouse ) {
                        if ( $warehouse['_id'] == $warehouse_id ) {
                            $this->target = $shop['marketplace'] ?? 'UNKNOW';
                            $this->target_shop_id = $shop['_id'];
                            $this->target_warehouse_id = $warehouse['_id'];
                        }
                    }
                }
            }
        }
    }

    public function setConfig($configDatas)
    {
        $res = [];
        foreach($configDatas as $configData) {
            if ( !isset($configData['key']) && !isset($configData['group_code']) ) {
                return [
                    'success' => false,
                    'message' => 'Key and Group Code is missing, use setGroupCode or send key'
                ];
            }
            
            if ( !isset($configData['group_code']) ) {
                $configData['group_code'] = 'default';
            }
            $configData['user_id'] = $this->user_id;

            $config = $this->mongo->findOne([
                'user_id' => $this->user_id,
                'key' => $configData['key'],
                'group_code' => $configData['group_code'],
            ]);

            $date = date('c');
    
            if ( isset($config) && count($config) > 0 ) {
                $configData['updated_at'] = $date;
                $this->mongo->updateOne(['_id' => $config['_id']], ['$set' => $configData]);
                $res[$configData['key']] = ['success' => true, 'message' => 'Config updated'];
            } else {
                $res[$configData['key']] = $this->saveNewConfig($configData);
            }
        }
        return $res;
    }

    public function saveNewConfig($configData)
    {
        
        if ( isset($configData['source_warehouse_id']) ) {
            $this->setSourceWarehouseId($configData['source_warehouse_id']);
            $configData['source_shop_id'] = $this->source_shop_id;
            $configData['source'] = $this->source;
        }

        if ( isset($configData['target_warehouse_id']) ) {
            $this->setTargetWarehouseId($configData['target_warehouse_id']);
            $configData['target_shop_id'] = $this->target_shop_id;
            $configData['target'] = $this->target;
        }

        if ( isset($configData['source_shop_id']) ) {
            $this->setSourceShopId($configData['source_shop_id']);
            $configData['source'] = $this->source;
        }

        if ( isset($configData['target_shop_id']) ) {
            $this->setTargetShopId($configData['target_shop_id']);
            $configData['target'] = $this->target;
        }

        if ( !isset( $configData['source'] ) && !isset( $configData['target'] ) ) {
            if ( !isset( $configData['source'] ) ) return [
                'success' => false,
                'message' => 'Source is UNKNOW'
            ];
            if ( !isset( $configData['target'] ) ) return [
                'success' => false,
                'message' => 'Target is UNKNOW'
            ];
        }

        $configData['create_at'] = date('c');

        $in = $this->mongo->insertOne($configData);

        if ( $in->getInsertedCount() ) {
            return [
                'success' => true,
                'message' => 'New Config Doc created'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'No New Config Doc created'
            ];
        }
        
    }

    public function getConfig($key = null)
    {
        $query = [];
        $aggregate = [];

        $query['user_id'] = $this->user_id;

        if ( !isset($this->source) ) $query['source'] = $this->source;

        if ( !isset($this->target) ) $query['target'] = $this->target;

        if ( !isset($key) ) $query['key'] = $key;
        
        if ( !isset($this->group_code) ) $query['group_code'] = $this->group_code;

        if ( !isset($this->target_shop_id) ) $query['target_shop_id'] = $this->target_shop_id;

        if ( !isset($this->target_warehouse_id) ) $query['target_warehouse_id'] = $this->target_warehouse_id;
        
        if ( !isset($this->source_warehouse_id) ) $query['source_warehouse_id'] = $this->source_warehouse_id;
        
        if ( !isset($this->source_shop_id) ) $query['source_shop_id'] = $this->source_shop_id;

        if ( !isset($this->app_tag) ) $query['app_tag'] = $this->app_tag;

        $aggregate[] = [
            '$match' => $query
        ];

        $configData = $this->mongo->aggregate($aggregate)->toArray();

        return $configData;

    }
    
}