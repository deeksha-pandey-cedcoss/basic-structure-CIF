<?php
namespace App\Core\Models;

use App\Core\Models\Base;

class RequestLog extends \App\Core\Models\BaseMongo
{
    protected $table = 'request_log';

    public $isGlobal = true;

    public function initialize()
    {
        $this->setSource($this->table);

        $this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
        //$this->setReadConnectionService('dbSlave');

        //$this->setWriteConnectionService('dbMaster');
    }

    public function get()
    {
        $collection = $this->getCollection();
        return $collection->find()->toArray();
    }

    public function insert($data)
    {
        return true;
        $collection = $this->getCollection();

        $log = $collection->findOne(['_id'=> (string)$data['_id']]);

        if($log)
        {
            $id = $data['_id'];
            unset($data['_id']);

            $updateResult = $collection->updateOne(
                ['_id' => (string)$id],
                ['$set' => $data]
            );

            if($updateResult->getMatchedCount()) {
                return true;                
            } else {
                return false;
            }
        }
        else
        {
            $data['created_at'] = new \MongoDB\BSON\UTCDateTime();

            if($collection->insertOne($data)) {
                return true;
            }
            else {
                $errors = implode(',', $requestLog->getMessages());
                $this->di->getLog()->logContent($errors, \Phalcon\Logger::CRITICAL, 'request_log_insert.log');
                return false;
            }
        }
    }
}