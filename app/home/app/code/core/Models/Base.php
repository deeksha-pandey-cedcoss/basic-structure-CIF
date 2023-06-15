<?php

namespace App\Core\Models;

use Phalcon\Mvc\Model;

class Base extends Model
{
    protected $table = '';
    protected $currentTransaction = false;
    protected $isGlobal = false;
    public function getTransactionByName($name = 'db')
    {
        return $this->di->getTransactionManager()->get($name, $this->getWriteConnectionService());
    }

    public function initTransaction($name = 'db')
    {
        $transaction = $this->getTransactionByName($name, $this->getWriteConnectionService());
        $this->setTransaction($transaction);
        return $this;
    }

    public function commitTransaction($name = 'db')
    {
        $transaction = $this->getTransactionByName($name);
        $transaction->commit();
    }

    public function rollbackTransaction($name = 'db', $msg = 'Rollback Transaction')
    {
        $transaction = $this->getTransactionByName($name);
        $transaction->rollback($msg);
    }

    public function initialize()
    {
        $this->setSource($this->table);
        if ($this->isGlobal) {
            $this->initializeDb($this->getMultipleDbManager()->getDefaultDb());
        } else {
            $this->initializeDb($this->getMultipleDbManager()->getDb());
        }
    }

    public function initializeDb($db)
    {
        if ($this->di->getConfig()->get('slave_enabled')) {
            $this->setReadConnectionService($db.'_slave'); /*can use selectReadConnection function for dynamically setting read service*/
            $this->setWriteConnectionService($db);
        } else {
            $this->setConnectionService($db);
        }
    }

    public function getMultipleDbManager()
    {
        return $this->di->getObjectManager()->get('\App\Core\Components\MultipleDbManager');
    }

    public function load($id)
    {
        return get_class($this)::findFirst($id);
    }

    public function getDbConnection()
    {
        return $this->di->get($this->getMultipleDbManager()->getDb());
    }

    public function __call($property, $arguments)
    {
        if (strpos($property, 'get') === 0) {
            $output = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($property, 3)));
            if (property_exists($this, $output)) {
                return $this->$output;
            } else {
                return null;
            }
        } elseif (strpos($property, 'set') === 0) {
            $output = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($property, 3)));
            $this->$output = $arguments[0];
            return $this;
        } else {
            return null;
        }
    }

    public function getData()
    {
        return $this->toArray();
    }

    public function setUserCache($key,$value,$userId = false){
        if ( !$userId ) $userId = $this->di->getUser()->id;
        $this->setCache('user_'.$key.'_'.$userId, $value);
    }

    public function setCache($key,$value){
        $this->di->getCache()->set($key, $value);
    }

    public function getUserCache($key, $userId = false){
        if ( !$userId ) $userId = $this->di->getUser()->id;
        return $this->getCache('user_'.$key.'_'.$userId);
    }
    public function getCache($key){
        $this->di->getCache()->get($key);
    }
}
