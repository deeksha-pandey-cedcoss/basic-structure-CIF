<?php

namespace App\Connector\Components;

use \Firebase\JWT\JWT;

class Helper extends \App\Core\Components\Base
{
    public function getAllShopsToConnect($userId, $targetMarketplace, $targetMarketplaceShopId = false)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');
        $user_details = $collection->findOne(['user_id' => (string)$userId]);
        $shops = [];
        foreach ($user_details['shops'] as $value) {
            if ($targetMarketplaceShopId) {
                if ($value['marketplace'] === $targetMarketplace && $value['_id'] == $targetMarketplaceShopId) {
                    $shops[] = $value;
                }
            }
        }
        return $shops;
    }

    public function updateFeedProgress($feedId, $progress, $message = '')
    {
        $queuedTask = \App\Connector\Models\QueuedTasks::findFirst([["_id" => $feedId]]);
        if ($queuedTask) {
            $initialProgress = $queuedTask->progress;
            $updatedProgress = $initialProgress + $progress;
            if ($updatedProgress < 99.9) {
                $queuedTask->progress = $updatedProgress;
                $queuedTask->updated_at = date('Y-m-d H:i:s');
                if ($message != '') {
                    $queuedTask->message = $message;
                }
                $queuedTask->save();
                $this->handleMessage($queuedTask->user_id);
                return $updatedProgress;
            } else {
                $queuedTask->delete();
                return 100;
            }
        }
        return false;
    }

    public function addNotification($userId, $message, $severity, $url = false)
    {
        if (is_array($message) && !empty($message) && isset($message['app_tag'])) {
            $appTag = $message['app_tag'];
            $message = $message['message'];
        } else {
            $appTag = $this->di->getAppCode()->getAppTag();
        }

        $notification = new \App\Connector\Models\Notifications;
        $notificationData = [
            'user_id' => $userId,
            'message' => $message,
            'severity' => $severity,
            'created_at' => date('c')
        ];

        if ($appTag) {
            $notificationData['app_tag'] = $appTag;
        }

        if ($url) {
            $notificationData['url'] = $url;
        }
        $notification->setData($notificationData);
        $response = $notification->save();
        return $response;
    }

    public function handleMessage($userId, $newNotification = false)
    {
        $params = [];
        $params['client_id'] = 2;
        $params['message'] = ['connection' => 'active check'];
        $notificationCount = 3;
        $response = $this->triggerMessage($params, $userId);
        if (isset($response['notified']) && $response['notified'] == 1) {
            $queuedTasks = new \App\Connector\Models\QueuedTasks;
            $notifications = new \App\Connector\Models\Notifications;
            $notification = $notifications->getAllNotifications(['count' => $notificationCount, 'activePage' => 0]);
            $notifications->clearReaminngNotification(50);
            $feed = $queuedTasks->getAllQueuedTasks();
            $params['message'] = [
                'feed' => $feed,
                'notification' => $notification,
                'new_notification' => $newNotification,
            ];
            $this->triggerMessage($params, $userId);
        }
    }

    public function triggerMessage($params, $userId)
    {
        if (!isset($params['message'])) {
            return false;
        }

        $token = [];
        $token["user_id"] = $userId;
        $privateKey = file_get_contents(BP . DS . 'app' . DS . 'etc' . DS . 'security' . DS . 'connector.pem');
        $params['token'] = JWT::encode($token, $privateKey, 'RS256');
        $postParams = json_encode($params);
        $url = "https://d7qznjkkge.execute-api.us-east-2.amazonaws.com/beta";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return json_decode($response, true);
    }

    public function saveTemporaryData($data, $userId)
    {
        $baseMongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $baseMongo->setSource('user_temp_' . $userId);
        $collection = $baseMongo->getCollection();
        $collection->deleteMany([]);
        $collection->insert($data);
        return true;
    }

    public function setQueuedTasks($message = 'Please wait while product(s) details are being uploading...', $code = '', $user_id = false)
    {
        if (!$user_id) $user_id = $this->di->getUser()->id;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('queued_tasks');
        $counter = $mongo->getCounter('queued_tasks');
        $queuedTask = $collection->insertOne([
            '_id' => $counter,
            'user_id' => $user_id,
            'message' => 'Please wait while product(s) details are being uploading...',
            'progress' => 0.00,
            'shop_id' => $user_id,
            'app_tag' => $this->di->getAppCode()->getAppTag(),
            'appTag' => $this->di->getAppCode()->getAppTag(),
            'additional_data' => $code,
        ]);
        return $counter;
    }

    public function getUsedTrialDays($userId)
    {
        $baseMongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $baseMongo->setSource('user_temp_' . $userId);
        $collection = $baseMongo->getCollection();
        $response = $collection->find([]);
        $response = $response->toArray();
        if (count($response)) {
            $daysUsed = $response[0]['trial_days_used'];
            return $daysUsed;
        }
        return false;
    }

    public function getUsedDays($userId)
    {
        $userConnector = \App\Connector\Models\User\Connector::findFirst(["user_id='{$userId}' AND code='shopify'"]);
        if ($userConnector) {
            $installationTime = strtotime($userConnector->installed_at);
            $timeDifference = time() - $installationTime;
            $daysUsed = ceil($timeDifference / (60 * 60 * 24));
            if ($daysUsed > $this->di->getConfig()->trial_period) {
                $daysUsed = $this->di->getConfig()->trial_period;
            }
            return $daysUsed;
        }
        return false;
    }

    public function getProfileSuggestions($productId, $userId = false)
    {
        if (!$userId) {
            $userId = $this->di->getUser()->id;
        }
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('profiles');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $profilesList = $collection->aggregate([
            [
                '$match' => [
                    'user_id' => (string)$userId,
                ],
            ],
            [
                '$project' => [
                    'query' => 1,
                    'profile_id' => 1,
                    'name' => 1,
                ],
            ],
        ], $options);
        $profilesList = $profilesList->toArray();
        if (count($profilesList)) {
            $profileSuggestions = [];
            $collection = $mongo->getCollectionForTable('product_container_' . $userId);
            foreach ($profilesList as $key => $value) {
                $filterQuery = $value['query'];
                $customizedFilterQuery = $this->di->getObjectManager()->get('\App\Connector\Models\Product')->prepareQuery($filterQuery);
                $customizedFilterQuery = [
                    '$and' => [
                        [
                            "details.source_product_id" => (string)$productId,
                        ],
                        $customizedFilterQuery,
                    ],
                ];
                $response = $collection->aggregate([
                    [
                        '$match' => $customizedFilterQuery,
                    ],
                ], $options);
                $response = $response->toArray();
                if ($response && count($response)) {
                    $profileSuggestions[] = $value;
                }
            }
            return ['success' => true, 'data' => $profileSuggestions];
        }
        return ['success' => false, 'message' => 'No profiles found'];
    }

    public function formatCoreDataForDetail($details)
    {
        $formatted_details = [];
        foreach ($details as $key => $value) {
            if ($key == "source_product_id") {
                $formatted_details['source_product_id'] = $details['container_id'];
            } elseif ($key == "visibility") {
                $formatted_details['visibility'] = "Catalog and Search";
            } elseif ($key == "_id") {
                continue;
            } else {
                $formatted_details[$key] = $value;
            }
        }
        return $formatted_details;
    }
}
