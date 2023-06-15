<?php

namespace App\Core\Models;

use Phalcon\Security;
use \Firebase\JWT\JWT;
use Phalcon\Mvc\Model\Query;

class Notifications extends BaseMongo
{
    public $port = '10000';
    public $socket = '';
    protected $table = 'notifications';
    /*
    public $sqlConfig;

    public function initialize()
    {
        $this->sqlConfig = $this->di->getObjectManager()->get('\App\Connector\Components\Data');
        $this->setSource($this->table);
        $this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
        //$this->setReadConnectionService('dbSlave');

        //$this->setWriteConnectionService('dbMaster');
    }
    */
    public function getNotificationsOfUser()
    {
        $userId = $this->di->getUser()->id;
        $allUserNotifications = Notifications::find(
            [
                [
                    "user_id" => $userId,

                ],
                'sort' => [
                    'created_at' => 1,
                ],
                'limit' => 100,
            ]
            
        );
        if (count($allUserNotifications)) {
            return ['success'=>true, 'message' => 'All notifications', 'data' => $allUserNotifications];
        } else {
            return ['success' => true, 'code' => 'no_notifications', 'message' => 'No notifications', 'data' => []];
        }
    }

    public function updateNotificationStatus($notificationDetails)
    {
        $userId = $this->di->getUser()->id;
        $notificationId = $notificationDetails['id'];
        $notification = Notifications::findFirst([["_id"=> $notificationId]]);
        $notification->seen = true;
        if ($notification->save()) {
            $notify = new Notifications;
            $notify->sendMessageToClient($userId);
            return ['success' => true, 'message' => 'Notification seen', 'code' => 'notification_seen'];
        } else {
            $errors = implode(',', $notification->getMessages());
            return ['success' => false, 'message' => 'Something went wrong', 'code' => $errors];
        }
    }

    public function updateMassNotificationStatus($notificationDetails)
    {
        $userId = $this->di->getUser()->_id;
        $seenNotifications = '';
        $count = 1;
        $ids = [];
        foreach ($notificationDetails as $key => $value) {
            $ids = $value['_id'];
            $count++;
        }
        $this->getCollection()->findAndModify([
            "query"=>[
                "_id" => ['$in' => $ids]
            ],
            "update"=>[
                "$set" => [
                    "seen"=>true
                ]
            ],
            "upsert" => true
        ]);
        
        if ($status) {
            $notify = new Notifications;
            $notify->sendMessageToClient($userId);
            return ['success' => true, 'message' => 'Notifications seen', 'code' => 'notifications_seen'];
        } else {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => 'something_went_wrong'];
        }
    }

    public function clearAllNotifications()
    {
        $userId = $this->di->getUser()->_id;
        $this->getConnection()->deleteMany([ "user_id" => $userId]);
        
        $this->sendMessageToClient($userId);
        if ($status) {
            return ['success' => true, 'message' => 'All notifications cleared successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to clear all notifications'];
        }
    }

    public function clearSeenNotifications()
    {
        $deleteQuery = 'DELETE FROM `notifications` WHERE `seen`=true';
        $this->getConnection()->deleteMany([ "seen" => true]);
        
        return $status;
    }

    public function sendMessageToClient($userId)
    {
        echo "<h2>TCP/IP Connection</h2>\n";
        /* Get the port for the WWW service. */
        $service_port = $this->port;

        $address = $this->di->getConfig()->server_ip;
        echo "Server address => " . $address;


        $context = stream_context_create();

        // local_cert must be in PEM format

        stream_context_set_option($context, 'ssl', 'local_cert', '/var/www/engine-cert-keys/fullchain2.pem');
        stream_context_set_option($context, 'ssl', 'local_pk', '/var/www/engine-cert-keys/privkey2.pem');
        stream_context_set_option($context, 'ssl', 'allow_self_signed', false);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);

        // Create the server socket
        $socket = stream_socket_client('ssl://' . $address . ':' . $service_port, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);




        /* Create a TCP/IP socket. */
        // $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        } else {
            echo "OK.\n";
        }

        echo "Attempting to connect to '$address' on port '$service_port'...";
        // $result = socket_connect($socket, $address, $service_port);
        /*if ($result === false) {
            echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        } else {
            echo "OK.\n";
        }*/

        $in = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n";
        $in .= "Host: 192.168.0.222\r\n";
        $in .= "userId: $userId\r\n\r\n";
        $out = '';
        echo "Sending HTTP HEAD request...";
        fwrite($socket, $in);
        echo "OK.\n";

        echo "Closing socket...";
        fclose($socket);
        echo "Closied.\n\n";
    }
}
