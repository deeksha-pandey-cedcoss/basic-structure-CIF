<?php

use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class UserTask extends Task
{   
    private $_args = false;

    public function getTokenAction()
    {

        $user = $this->getArg('-u');
        $user = \App\Core\Models\User::findFirst([["username"=>$user]]);
        if ($user ) {
            echo PHP_EOL.$user->getToken('+365 days', false, false).PHP_EOL;
        } else {
            echo PHP_EOL.'Check your user id '.PHP_EOL;
        }
            
    }

    public function createAction()
    {
        $user = $this->getArg('-u');
        $password = $this->getArg('-p');
        $email = $this->getArg('-e');
        $type = $this->getArg('-t');
        $userModel = $this->di->getObjectManager()->create('\App\Core\Models\User');
        $userModel->createUser([
            'username' => $user,
            'email' => $email,
            'password'=> $password,
        ], $type);
        echo PHP_EOL.'User Created Successfully..'.PHP_EOL;
    }


    public function updatePasswordAction()
    {
        $user = $this->getArg('-u');
        $password = $this->getArg('-p');
        $user = \App\Core\Models\User::findFirst([["username"=>$user]]);
        $hash = $user->getHash($password);
        
        $user->setPassword($hash);
        $user->save();
        
        echo 'Updated Password to : '.$password;
    }

    public function getArg($code){
        if(!$this->_args){
            $this->_args = $this->getParams();
        }
        return $this->_args[$code] ?? false;
    }
    public function getParams(){
        global $argv;
        $args = [];
        if(count($argv)<4)
            return [];
        else{
            for($i=3;$i<count($argv);$i+=2){
                $args[$argv[$i]] = $argv[$i+1]??false;
            }
        }
        return $args;
    }
}