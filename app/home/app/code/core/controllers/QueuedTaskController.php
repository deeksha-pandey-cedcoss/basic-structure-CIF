<?php

namespace App\Core\Controllers;

class QueuedTaskController extends BaseController
{
    public function getAllAction()
    {
        $queuedTask = new \App\Core\Models\QueuedTask;
        return $this->prepareResponse($queuedTask->getQueuedTaskOfUser());
    }

    public function getAllNotificationsAction()
    {
        $notifications = new \App\Core\Models\Notifications;
        return $this->prepareResponse($notifications->getNotificationsOfUser());
    }

    public function updateNotificationStatusAction()
    {
        $notifications = new \App\Core\Models\Notifications;
        return $this->prepareResponse($notifications->updateNotificationStatus($this->di->getRequest()->get()));
    }

    public function updateMassNotificationStatusAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $notifications = new \App\Core\Models\Notifications;
        return $this->prepareResponse($notifications->updateMassNotificationStatus($rawBody));
    }

    public function clearAllNotificationsAction()
    {
        $notifications = new \App\Core\Models\Notifications;
        return $this->prepareResponse($notifications->clearAllNotifications());
    }
}
