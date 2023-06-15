<?php

namespace App\Connector\Components;

use Phalcon\Events\Event;

class UserCreateEvent extends \App\Core\Components\Base
{

    /**
     * @param Event $event
     * @param $myComponent
     */
    public function createAfter(Event $event, $myComponent)
    {
        /*$user_db = $this->di->getObjectManager()->get('\App\Core\Components\MultipleDbManager')->getCurrentDb();
        $user_id = $myComponent->id;
        $connection = $this->di->get($user_db);
        try {
            $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
            $mongo->setSource("product_container");
            $collection = $mongo->getCollection();
            $collection->createIndex([
                "details.source_product_id"=> 1,
                'variants.source_variant_id' => 1]);
            $collection->createIndex([
                "details.title"=> "text",
                "details.short_description"=> "text",
                "details.long_description"=> "text"
            ]);

            $coreConfig = $this->di->getCoreConfig();
            $connection->begin();
            if ($coreConfig->get('enable_warehouse_feature')) {
                $query2 = "INSERT INTO `warehouse` (`id`, `merchant_id`, `name`, `status`, `order_target`, `order_target_shop`, `country`, `state`, `city`, `region`, `street`, `zipcode`, `longitude`, `latitude`, `handler`) VALUES (NULL, '".$user_id."', 'Ced', '1', 'connector', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'default');";
                $connection->query($query2);
            }
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollback();
        }*/
    }
}
