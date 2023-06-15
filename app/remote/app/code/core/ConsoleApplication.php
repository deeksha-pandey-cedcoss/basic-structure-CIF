<?php

namespace App\Core;

use \Phalcon\Config;
use \Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use \Phalcon\Di\DiInterface;
use \Phalcon\Events\Manager;

class ConsoleApplication extends \Phalcon\Cli\Console
{
    use Traits\Application;
    public function __construct(DiInterface $di)
    {
        global $argv;
        parent::setDI($di);
        $di->set('app', $this);
        $this->di->get('\App\Core\Components\Log');
     
        $this->di->get('\App\Core\Components\Cache');
        
        $this->registerAllModules();
        $this->di->set('registry', new \App\Core\Components\Registry);
        $this->loadAllConfigs();
        $this->registerDi();
        $this->loadDatabase();
        $this->di->setShared('objectManager', '\App\Core\Components\ObjectManager');
        $this->di->set('coreConfig', new \App\Core\Models\Config);
        $this->di->setShared('transactionManager', '\App\Core\Models\Transaction\Manager');

        /* set rollback pendent for rollback in case of any exception or error */
        $this->di->getTransactionManager()->setRollbackPendent(true);

        $this->di->setTokenManager($this->di->getObjectManager()->get('App\Core\Components\TokenManager'));
        $this->di->setRequest($this->di->getObjectManager()->get('Phalcon\Http\Request'));

        if (!isset($argv[3])|| (isset($argv[3]) && $argv[3]!='install')) {
            $this->setProxyUserAndToken();
        }
        $this->hookEvents();
    }
    
    

    public function registerDi(){
        foreach ($this->di->getConfig()->di as $key => $class) {
            $this->di->set(
                $key,$class
            );
        }
    }

    public function setProxyUserAndToken()
    {
        $user = \App\Core\Models\User::findFirst([["username"=>"admin"]]);
        $user->id = (string)$user->_id;
        $user_id = (string)$user->getId();
        $decodedToken = [
                'role'=>'admin',
                'user_id'=> $user_id,
            ];
        $this->di->getRegistry()->setDecodedToken($decodedToken);

        //$user = \App\Core\Models\User::findFirst(['user_id' => $decodedToken['user_id']]);
        $this->di->getRegistry()->setRequestingApp('console');
        if ($user) {
            $this->di->setUser($user);
        }
    }

    

    public function getAllModules()
    {
        $modules = $this->getSortedModules();
        $activeModules = [];
        foreach ($modules as $mod) {
            foreach ($mod as $module) {
                $activeModules[$module['name']] = $module['active'];
            }
        }
        return $activeModules;
    }



    
}
