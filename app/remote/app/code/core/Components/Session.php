<?php

namespace App\Core\Components;

use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream as FileAdapter;

class Session extends Base
{
    protected $session;
    public function setDi(\Phalcon\Di\DiInterface $di):void
    {
        parent::setDi($di);
        $this->session = new \Phalcon\Session\Adapter\Stream();
    }

    public function start(){
        $this->session->open('/app/tmp/session','connector');
    }

    public function has($id){
        if( $this->session->read($id) == ''){
            return false;
        }
        return true;
    }

    public function set($key, $value){
        return $this->session->write($key, $value);
    }

    public function get($key){
        return $this->session->read($key);
    }
}
