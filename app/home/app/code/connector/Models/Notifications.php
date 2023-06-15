<?php

namespace App\Connector\Models;

use App\Core\Models\BaseMongo;

class Notifications extends BaseMongo
{
    protected $table = 'notifications';

    public function initialize()
    {
        $this->setSource($this->table);
        $this->setConnectionService($this->getMultipleDbManager()->getDb());
    }

    public function getAllNotifications($params)
    {
        $userId = $this->di->getUser()->id;
        $count = 100;
        $activePage = 0;
        $aggregate = [];
        if (isset($params['count']) && isset($params['activePage'])) {
            $count = $params['count'];
            $activePage = $params['activePage'] - 1;
            $activePage = $activePage * $count;
        }else if(isset($params['count'])){
            $count = $params['count'];
        }

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

        if (isset($params['severity'])) {
            $aggregate[] = [
                '$match' => [
                    "severity" => $params['severity']
                ],
            ];
        }

        $aggregate[] = [
            '$skip' => (int) $activePage,
        ];

        $aggregate[] = [
            '$limit' => (int) $count,
        ];
    
        $collection = $this->getCollection();

        $notifications = $collection->aggregate($aggregate);
        $notificationsCount = $collection->count(["user_id" => $userId]);
        $notifications = $notifications->toArray();
      
        return ['success' => true, 'data' => ['rows' => $notifications, 'count' => $notificationsCount]];
    }

    public function clearAllNotifications()
    {
        $userId = $this->di->getUser()->id;
        $baseModel = $this->di->getObjectManager()->get('App\Core\Models\Base');
        $connection = $baseModel->getDbConnection();
        $query = 'DELETE FROM notifications WHERE user_id = ' . $userId;
        $response = $connection->query($query);
        $files = glob(BP . DS . 'var' . DS . 'upload-report' . DS . 'google' . DS . 'upload_report_' . $userId . '_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $files = glob(BP . DS . 'var' . DS . 'order-sync-report' . DS . 'google' . DS . 'order_sync_' . $userId . '_*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return ['success' => true, 'message' => 'All your activities are cleared.'];
    }

    public function clearReaminngNotification($limit = 100)
    {
        $userId = $this->di->getUser()->id;
        $baseModel = $this->di->getObjectManager()->get('App\Core\Models\Base');
        $connection = $baseModel->getDbConnection();
        $query = 'DELETE FROM notifications where user_id = ' . $userId . ' AND id NOT IN (SELECT id FROM (SELECT id FROM notifications WHERE user_id = ' . $userId . ' ORDER BY id DESC LIMIT ' . $limit . ') foo)';
        $response = $connection->query($query);

        $files = glob(BP . DS . 'var' . DS . 'upload-report' . DS . 'google' . DS . 'upload_report_' . $userId . '_*');
        $files = array_reverse($files);
        $i = 1;
        foreach ($files as $file) {
            if (is_file($file) && $i > $limit) {
                $i++;
                unlink($file);
            }
        }

        $files = glob(BP . DS . 'var' . DS . 'order-sync-report' . DS . 'google' . DS . 'order_sync_' . $userId . '_*');
        $j = 1;
        $files = array_reverse($files);
        foreach ($files as $file) {
            if (is_file($file) && $j > $limit) {
                unlink($file);
            }
            $j++;
        }
    }
}
