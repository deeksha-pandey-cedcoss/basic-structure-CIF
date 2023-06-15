<?php
namespace App\Connector\Models\User;

use Exception;
use App\Core\Models\User\Details;

class Shop extends Details
{
	protected $table = 'user_details';

    protected $isGlobal = true;

    public function addApp($remoteShopId , $app , $userId = false){
    	 if(!$userId){
    	 	$userId = $this->di->getUser()->id;
    	 }

    	 $user = $this->getPhpCollection();

    	 return $user->updateOne(
	            [
	                "_id"=>new \MongoDB\BSON\ObjectId($userId)
	            ],
	            [
	                '$push'=>[
	                    'shops.$[shop].apps'=>$app
	                ]
	            ],
	            [
	                'arrayFilters' => [
	                    [
	                        'shop.remote_shop_id' => $remoteShopId
	                    ]
	                ]
	            ]

	        );


    }


    public function addWebhook($shop , $appCode, $webhook , $userId = false){
    	if(isset($shop['remote_shop_id'])){
    		if(isset($shop['apps'])){
    			$webhooNeedToSet = [];
    			$checkKeys = [];
    			foreach ($shop['apps'] as $app) {
    				if($app['code'] == $appCode){
    					if(isset($app['webhooks'])){
    						foreach ($app['webhooks'] as $appWebhook) {
	    						if(!isset($checkKeys[$appWebhook['code']])){
	    							$webhooNeedToSet[] = ['code'=>$appWebhook['code'],'dynamo_webhook_id'=> $appWebhook['dynamo_webhook_id'] ?? ''];
	    							
	    							$checkKeys[$appWebhook['code']] = 1;
	    						} 
	    					}
    					}


    					foreach ($webhook as $webhookId => $webhookData) {
    						if(!isset($checkKeys[$webhookId])){
    							$webhooNeedToSet[] = ['code'=>$webhookId ,'dynamo_webhook_id'=> $webhookData['dynamo_webhook_id'] ?? ''];
    							$checkKeys[$webhookId] = 1;
    						} 
    					}

    				}
    			}
    		}
    		$remoteShopId = $shop['remote_shop_id'];

    		if(!$userId){
	    	 	$userId = $this->di->getUser()->id;
	    	}
	    	$user = $this->getPhpCollection();

	    	 return $user->updateOne(
	            [
	                "_id"=>new \MongoDB\BSON\ObjectId($userId),
	            ],
	            [
	                '$set'=>[
	                    'shops.$[shop].apps.$[app].webhooks'=>$webhooNeedToSet
	                ]
	            ],
	            [
	            'arrayFilters' => [
	                    [
	                        'shop.remote_shop_id' => $remoteShopId,
	                    ],
	                    [
	                    	'app.code' => $appCode
	                    ]
	                ]
	            ]

	        );

    	}

    }
}
