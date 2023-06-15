<?php
namespace App\Connector\Components;

class Hook extends \App\Core\Components\Base
{
    public function TemporarlyUninstall($data,$user_id=false)
    {
        if (!$user_id) {
            $user_id = $this->di->getUser()->id;
        }
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array'],'projection'=>['shops.$'=>1,'_id'=>0]];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $userDetails = $collection->findOne(['user_id' => $user_id, 'shops.apps.code' => $data['code']],$options);
        $shop  = $userDetails['shops'][0];

        $appData = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
            ->init($shop['marketplace'],false,$data['code'])
            ->call('/app',[],['shop_id'=>$shop['remote_shop_id']], 'GET');
        $erase_data_after_days = $appData['erase_data_after_uninstall'];
        $erase_data_after_date = date('c', strtotime("+$erase_data_after_days days"));

        $res = $collection->updateOne(
            ['user_id' => $user_id, 'shops.apps.code' => $data['code']],
            ['$set' => ['shops.$.uninstall_status' => 'ready_to_uninstall','shops.$.uninstall_date' => date('c'),'shops.$.erase_data_after_date'=>$erase_data_after_date]]
        );
        print_r($res);
        return true;
    }


    public function shopErarser()
    {
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array'],/*'projection'=>['shops.$'=>1,'_id'=>0]*/];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $users = $collection->find([
            'shops.erase_data_after_date' => [ '$gte' => date('c')],
            'shops.uninstall_status' => ['$exists' => true]],$options)
            ->toArray();

        $user_name = [];
        foreach ($users as $key => $user) {
            $user_name[] =  $user['username'];
            $user_id = $user['user_id'];
            $options = ["typeMap" => ['root' => 'array', 'document' => 'array'],'projection'=>['shops.$'=>1,'_id'=>0]];
            $shops = $collection->find([
                'shops.erase_data_after_date' => [ '$gte' => date('c')],
                'shops.uninstall_status' => ['$exists' => true]],$options)
                ->toArray();
            $shops = $shops[0]['shops'];
            if (count($shops)==1){
                $clientData = [
                    'user_id' => $user_id,
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'update_at' => date('c'),
                    'uninstall_date' => $shops[0]['uninstall_date']
                ];
                $query = ['user_id' => (string)$user_id];

                $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
                $collection = $mongo->getCollectionForTable('product_container');
                $clientData['product_count'] = $collection->count($query);
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('amazon_product_container');
                $clientData['amazon_product_container'] = $collection->count($query);
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('queued_tasks');
                $collection->deleteMany($query);


                $collection = $mongo->getCollectionForTable('configuration');
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('profiles');
                $clientData['profiles_count'] = $collection->count($query);
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('profile_settings');
                $collection->deleteMany(['merchant_id' => (string)$user_id]);

                $collection = $mongo->getCollectionForTable('amazon_listing');
                $clientData['amazon_listing_count'] = $collection->count($query);
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('user_details');
                $clientData['account_count'] = count($collection->findOne($query)['shops'] ?? []);
                $collection->deleteOne($query);

                $collection = $mongo->getCollectionForTable('transaction_log');
                $clientData['transaction_log'] = $collection->find($query)->toArray();
                $collection->deleteMany($query);

                $collection = $mongo->getCollectionForTable('active_plan');
                $clientData['active_plan'] = $collection->find($query)->toArray();
                $collection->deleteMany($query);

                // Set the user data in  new table
                $collection = $mongo->getCollectionForTable('uninstall_users');
                $collection->updateOne(['user_id' => $user_id], [
                    '$set' => $clientData,
                    '$setOnInsert' => ['created_at' => date('c')]
                ], ['upsert' => true]);

                $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                    ->init($shops[0]['marketplace'], 'true',$shops[0]['apps'][0]['code'])
                    ->call('app-shop', [], ['shop_id'=> $shops[0]['remote_shop_id']], 'DELETE');

            }else{
                $filters['user_id'] = $user_id;
                $collection->updateMany(
                    $filters,
                    ['$pull' => ['shops' => ['$and'=>[['uninstall_status' => ['$exists' => true]],['erase_data_after_date' => [ '$gte' => date('c')]]]]]]
                );
            }

        }

        $this->di->getLog()->logContent('Erasing this clients : ' . json_encode($user_name), 'info', 'storeEraser.log');
        return true;
    }

}
