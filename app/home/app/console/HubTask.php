<?php

use Phalcon\Cli\Task;

class HubTask extends Task
{
	public function syncDataAction() {
		$query = 'SELECT hsd.user_id FROM  `hubspot_shop_details` AS hsd INNER JOIN  `magento_shop_details` AS msd ON hsd.oauth_is_valid = 1
					AND hsd.user_id = msd.user_id';
		$baseModel = $this->di->getObjectManager()->get('App\Core\Models\Base');
        $connection = $baseModel->getDbConnection();
        $userDetails = $connection->fetchAll($query);
        foreach ($userDetails as $key => $value) {
        	if ($this->di->getConfig()->enable_rabbitmq) {
	        	$this->addSyncDataQueue($value['user_id']);
	        } else {
	        	$this->di->getObjectManager()
                    ->get('\App\Hubspot\Components\Helper')
                    ->syncAllData($value['user_id']);
	        }
        }
	}

	public function addSyncDataQueue($userId) {
        $handlerData = [
            'type' => 'class',
            'class_name' => 'Qhandler',
            'method' => 'consumerSyncUserData',
            'queue_name' => 'sync_user_data',
            'own_weight' => 100,
            'data' => [
                'userId' => $userId
            ]
        ];
        if($this->di->getConfig()->enable_rabbitmq_internal){
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            return $helper->createQueue($handlerData['queue_name'],$handlerData);
        }
        else {
            $request = $this->di->get('\App\Core\Components\Helper')
                    ->curlRequest($this->di->getConfig()->rabbitmq_url . '/rmq/queue/create', $handlerData, false);
            $responseData = json_decode($request['message'], true);
            return $request['feed_id'];
        }
    }
}