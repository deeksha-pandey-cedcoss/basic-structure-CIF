<?php

namespace App\Connector\Models;

use App\Core\Models\BaseMongo;
use Phalcon\Mvc\Model\Message;

class QueuedTasks extends BaseMongo {
	protected $table = 'queued_tasks';

	public function initialize()
    {
        $this->setSource($this->table);
        $this->setConnectionService($this->getMultipleDbManager()->getDb());
    }

    public function getAllQueuedTasks($params) {
    	$userId = $this->di->getUser()->id;
        $collection = $this->getCollection();
        $aggregate = [];

        if(isset($params['app_tag'])){
            $appTag = $params['app_tag'];
        }else{
            $appTag = $this->di->getAppCode()->getAppTag();
        }
        
        $aggregate[] = [
            '$match' => [
                "app_tag" => $appTag
            ],
        ];

        $aggregate[] = [
            '$match' => [
                "user_id" => $userId,
            ],
        ];

        $aggregate[] = [
            '$sort' => ['_id' => -1]
        ];
        $queuedTasks = $collection->aggregate($aggregate);
        $count = $collection->count(["user_id" => $userId]);
        $queuedTasks = $queuedTasks->toArray();
    	// $queuedTasks = $collection->find(["user_id" => $userId]);
    	// $count = count($queuedTasks);
    	// $queuedTasks = $queuedTasks->toArray();
    	return ['success' => true, 'data' => [
    		'rows' => $queuedTasks,
    		'count' => $count
    	]];
    }
 
    // protected function _postSave($success, $exists)
    // {
    //     try {
    //         if ($success === true) {
    //             $helper = $this->di->getObjectManager()->create('\App\Connector\Components\Helper');
    //             $helper->handleMessage($this->user_id);
    //         }
    //     } catch (\Exception $e) {
    //         die($e->getMessage());
    //     }
    //     return parent::_postSave($success, $exists);
    // }

    public function setQueuedTask($userId, $queuedData) {
        if(empty($queuedData)){
            return ['success' => false, 'message' => 'QueueData missing.'];
        }
        
        $appTag = '';
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $collection = $this->getCollection();

        if(isset($queuedData['app_tag'])){
            $appTag = $queuedData['app_tag'];
        }else{
            $appTag = $this->di->getAppCode()->getAppTag();
        }
        
        $salesChannel = isset($queuedData['sales_channel'])?$queuedData['sales_channel']:false;

        if ($appTag && !$salesChannel) {
            $queuedData['type'] = $queueType = $appTag.'_'.$queuedData['type'];
        }else{
            $queueType = $queuedData['type'];
        }

        if(!isset($queuedData['user_id'])){
            $queuedData['user_id'] = $userId;
        }
        if(!isset($queuedData['app_tag'])){
            $queuedData['app_tag'] = $appTag;
        }
        if(!isset($queuedData['created_at'])){
            $queuedData['created_at'] = date('c');
        }
        
        // $checkQueue = $collection->count(['user_id' => $userId,'type' => $queueType,'shop_id' => (string)$queuedData['shop_id']]);

        $query = ['user_id' => $userId, 'type' => $queueType];
        if(isset($queuedData['shop_id'])) {
            $queuedData['shop_id'] = (string)$queuedData['shop_id'];
            $query['shop_id'] = $queuedData['shop_id'];
        }
        $checkQueue = $collection->count($query);
        if($checkQueue){
            return false; // return false if queued_process already exists
        }

        $status = $collection->insertOne($queuedData);
        if($status){
            return (string)$status->getInsertedId();
        }

        return ['success'=> false, 'message' => 'Something went wrong. Failed to create Queue.'];
    }
}