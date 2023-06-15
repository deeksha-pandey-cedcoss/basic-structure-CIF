<?php

namespace App\Core\Models;

use Phalcon\Security;
use \Firebase\JWT\JWT;
use Phalcon\Mvc\Model\Query;

class Test extends Base
{
    protected $table = 'test';

    public function initialize()
    {
        $this->setSource($this->table);
        $this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
        //$this->setReadConnectionService('dbSlave');
        //$this->setWriteConnectionService('dbMaster');
    }
    public function set($u,$p,$a)
    {
    	$config = Test::findFirst("username='{$u}'");
        if ($config) {
            $config->setPassword($p)->save();
        } else {
        	$this->setUsername($u)->setPassword($p)->setAuth($a)->save();
    	}
    }
    public function get($u)
    {
        $config = Test::findFirst("username='{$u}'");
        // print_r($config);die();
        if ($config) {
             return $config->getUsername();
        } else {
            return ['success'=>'false'];
        }
    }

}
