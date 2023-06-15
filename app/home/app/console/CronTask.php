<?php

use App\Rmq\Components\App\DeploymentConfig;
use App\Rmq\Components\MessageQueue\EnvelopeFactory;
use App\Rmq\Components\Config;
use App\Rmq\Components\Rqueue;
use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class CronTask extends Task
{
    public function updateFeedAction() {}

    public function syncCanceledOrdersAction() {
        $authorisedUsers = \App\Connector\Models\User\Connector::find(["code='google'"]);
        foreach ($authorisedUsers as $key => $value) {
            $this->di->getObjectManager()->get('\App\Google\Models\SourceModel')->syncCanceledOrders($value->user_id);
        }
        return true;
    }

    public function statusUpdateAction(){

        $getallUsers=\App\Core\Models\User::find(['columns' => 'id']);
        if($getallUsers){
            $getallUsers=$getallUsers->toArray();
            //GetAll data in google_product_userid table

            foreach ($getallUsers as $key => $value) {

                $handlerData = [
                    'type' => 'full_class',
                    'class_name' => '\App\Google\Components\Helper',
                    'method' => 'updateStatusReq',
                    'queue_name' => $this->di->getObjectManager()->get('\App\Google\Components\Helper')->getQueueName('google_status_update',$value['id']),
                    'own_weight' => 100,
                    'user_id' => $value['id'],
                ];
                $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
                $helper->createQueue($handlerData['queue_name'], $handlerData);
            } //for end

        }

    }

    public function orderSyncAction() {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable("user_details");
        $user_details = $collection->find(['shops.marketplace' => 'facebook'])->toArray();
        foreach ( $user_details as $key => $value ) {
            $this->di->getLog()->logContent('Stated for user_id : ' . $value['user_id'],'info','orderSync.log');
            $this->di->getObjectManager()->get('\App\Facebookhome\Components\Orders')
                ->init($value)
                ->syncOrdersManually();
        }
    }

    public function syncSheetAction() {
        echo $this->di->getObjectManager()->get('App\Frontend\Models\Sheet')->sync();
    }

}
