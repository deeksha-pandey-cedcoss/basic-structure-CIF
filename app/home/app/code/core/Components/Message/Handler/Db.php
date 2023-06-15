<?php

namespace App\Core\Components\Message\Handler;

class Db extends Base
{
    public function pushMessage($data){
        $model = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $model->setSource('queue_messages');
        $queueData = [];
        $queueData['_id'] = (string)$this->getCounter('queue_message_id');
        $queueData['data'] = $data;
        $model->setData($queueData)->save();
        return $queueData['_id'];
    }

    /**
     * @param $referenceId
     * @return mixed
     */
    public function processMessage($msgArray){
        $msgArray['type'] = $msgArray['type']??'default';
        $logger = $this->di->getLog();
        $queueName = $msgArray['queue_name'];
        if ($msgArray['type']=='url') {
            $response = $this->callCurl($msgArray['url'], $msgArray['data'], false);
            $file = 'queue/process.log';
            $logger->logContent('responseCode: '.$response['responseCode'], \Phalcon\Logger::DEBUG, $file);
            if ($response['responseCode']==200) {
                return true;
            } elseif ($response['responseCode']==503) {
                $data = ['message'=>$msgArray,'queue_name'=>$queueName];
                $this->createQueue('process_maintenance', $data);
                $logger->logContent('Source site is under maintenance.pushing data in maintenance queue', \Phalcon\Logger::DEBUG, $file);
                return true;
            }
            $logger->logContent($queueName.':'.print_r($msgArray, true), \Phalcon\Logger::DEBUG, $file);

            $logger->logContent('Url:'.$baseUrl.$msgArray['url'].PHP_EOL.print_r($response, true), \Phalcon\Logger::DEBUG, $file);
            return false;
        } elseif ($msgArray['type']=='full_class') {
            if (isset($msgArray['class_name'])) {
                $obj = $this->getDi()->getObjectManager()->get($msgArray['class_name']);

                $method = $msgArray['method'];
                $response = $obj->$method($msgArray);
                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');

                /* tdo if class not found */
                return true;
            }
        } elseif ($msgArray['type']=='class') {
            if (isset($msgArray['class_name'])) {
                $obj = $this->getDi()->getObjectManager()->get('App\\Rmq\\Handlers\\'.$msgArray['class_name']);

                $method = $msgArray['method'];
                $response = $obj->$method($msgArray);
                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');
                /* tdo if class not found */
                return true;
            }
        } else {
            $obj = $this->getDi()->getObjectManager()->get('App\\Rmq\\Handlers\\Default1');
            if (isset($msgArray['method'])) {
                $method = $msgArray['method'];
                $response = $this->$method($msgArray);
                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');
                return true;
                /*todo handle without method message*/
            }
        }
    }
}
