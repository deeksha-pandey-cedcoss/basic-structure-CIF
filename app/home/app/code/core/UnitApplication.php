<?php

namespace App\Core;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;
use Phalcon\Logger;
use Phalcon\Mvc\Router;

class UnitApplication extends \Phalcon\Mvc\Application
{

    /**
     * Application Constructor
     *
     * @param \Phalcon\Di\DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        /**
         * Sets the parent DI and register the app itself as a service,
         * necessary for redirecting HMVC requests
         */
        parent::setDI($di);
        $di->set('app', $this);
        $this->di->get('\App\Core\Components\Log');
        $this->di->get('\App\Core\Components\Cache');
        $this->registerAllModules();
        $this->di->set('registry', new \App\Core\Components\Registry);
        $this->loadAllConfigs();
        $this->di->setShared('objectManager', '\App\Core\Components\ObjectManager');
        $this->registerDi();

        $this->loadDatabase();

        $this->di->setShared('transactionManager', '\App\Core\Models\Transaction\Manager');




        /* set rollback pendent for rollback in case of any exception or error */
        $this->di->getTransactionManager()->setRollbackPendent(true);

        $this->di->set('coreConfig', new \App\Core\Models\Config);
        //  $this->di->getUrl()->setBaseUri('http://192.168.0.49/phalcon_git/tez/public/');
        $this->di->getUrl()->setBaseUri($this->di->getConfig()->backend_base_url);
        $this->di->setTokenManager($this->di->getObjectManager()->get('App\Core\Components\TokenManager'));
        $this->hookEvents();
        $this->registerRouters();
        $this->setProxyUserAndToken();
    }
    public function setProxyUserAndToken()
    {
        $decodedToken = [
            'role'=>'admin',
            'user_id'=>1,
        ];
        $this->di->getRegistry()->setDecodedToken($decodedToken);
        $user = \App\Core\Models\User::findFirst($decodedToken['user_id']);
        $this->di->getRegistry()->setRequestingApp('unittesting');
        if ($user) {
            $this->di->setUser($user);
        }
    }

    public function registerDi(){
        foreach ($this->di->getConfig()->di as $key => $class) {
            $this->di->getObjectManager()->get($key);
        }
    }
    public function registerAllModules()
    {
        // Register the installed modules
        $modules = [];
        $namespaces = [];
        $loader = $this->getDi()->getLoader();

        foreach ($this->getAllModules() as $moduleName => $active) {
            if ($active) {
                $namespace = [
                    'App\\' . ucfirst($moduleName) . '\Controllers' => CODE . DS . $moduleName . DS . 'controllers' . DS,
                    'App\\' . ucfirst($moduleName) . '\Models' => CODE . DS . $moduleName . DS . 'Models' . DS,
                    'App\\' . ucfirst($moduleName) . '\Components' => CODE . DS . $moduleName . DS . 'Components' . DS,
                    'App\\' . ucfirst($moduleName) . '\Setup' => CODE . DS . $moduleName . DS . 'Setup' . DS,
                    'App\\' . ucfirst($moduleName) . '\Handlers' => CODE . DS . $moduleName . DS . 'Handlers' . DS,
                    'App\\' . ucfirst($moduleName) . '\Api' => CODE . DS . $moduleName . DS . 'Api' . DS,

                ];

                $namespaces = array_merge($namespaces, $namespace);

                $modules[$moduleName] = [
                    'className' => 'App\\' . ucwords($moduleName) . '\Register',
                    'path' => CODE . DS . $moduleName . DS . 'Register.php',
                ];
            }
        }

        $loader->registerNamespaces($namespaces);
        $loader->register();
        $this->registerModules($modules);
    }

    /*
    public function getAllModules()
    {
        return $this->di->getObjectManager()->get('App\Core\Components\Helper')->getAllModules();
    }
    */
    public function getAllModules()
    {
        $filePath = BP . DS . 'app' . DS . 'etc' . DS . 'modules.php';
        if (file_exists($filePath)) {
            return require $filePath;
        } else {
            $modules = $this->getSortedModules();
            $activeModules = [];
            foreach ($modules as $mod) {
                foreach ($mod as $module) {
                    $activeModules[$module['name']] = $module['active'];
                }
            }
            $handle = fopen($filePath, 'w+');
            fwrite($handle, '<?php return ' . var_export($activeModules, true) . ';');
            fclose($handle);
            return $activeModules;
        }
    }

    public function getSortedModules()
    {
        $modules = [];
        foreach (new \DirectoryIterator(CODE) as $fileInfo) {
            if (!$fileInfo->isDot() && $fileInfo->isDir()) {
                $filePath = CODE . DS . $fileInfo->getFilename() . DS . 'module.php';
                if (file_exists($filePath)) {
                    $module = require $filePath;
                    if (isset($module['sort_order'])) {
                        $modules[$module['sort_order']][] = $module;
                    } else {
                        $modules[9999][] = $module;
                    }
                }
            }
        }
        ksort($modules, 1);
        return $modules;
    }


    public function loadAllConfigs()
    {
        if ($this->di->getCache()->get('config')) {
            $config = $this->di->getCache()->get('config');
        } else {
            $config = new Config([]);
            foreach ($this->getAllModules() as $module => $active) {
                if ($active) {
                    $filePath = CODE . DS . $module . DS . 'etc' . DS . 'config.php';
                    if (file_exists($filePath)) {
                        $array = new \Phalcon\Config\Adapter\Php($filePath);
                        $config->merge($array);
                    }
                    $systemConfigfilePath = CODE . DS . $module . DS . 'etc' . DS . 'system.php';
                    if (file_exists($systemConfigfilePath)) {
                        $array = new \Phalcon\Config\Adapter\Php($systemConfigfilePath);
                        $config->merge($array);
                    }
                }
            }

            $filePath = BP . DS . 'app' . DS . 'etc' . DS . 'unit-config.php';
            if (file_exists($filePath)) {
                $array = new \Phalcon\Config\Adapter\Php($filePath);
                $config->merge($array);
            }
            $this->di->getCache()->set('config', $config);
        }
        $this->di->set('config', $config);
    }

    public function loadDatabase()
    {

        $isMongo = false;
        foreach ($this->di->getConfig()->databases as $key => $database) {
            if ($database['adapter'] == 'Mongo') {
                $isMongo = true;
                $this->di->set(
                    $key,
                    function () use ($database) {
                        if (!isset($database['username'])) {
                            $dsn = 'mongodb://' . $database['host'];
                            $mongo = new \Phalcon\Db\Adapter\MongoDB\Client($dsn);
                        } else {
                            /* $dsn = sprintf(
                                 'mongodb://%s:%s@%s',
                                 $database['username'],
                                 $database['password'],
                                 $database['host']
                             );*/
                            $dsn = 'mongodb://' . $database['host'];
                            $mongo = new \Phalcon\Db\Adapter\MongoDB\Client($dsn,array("username" => $database['username'], "password" =>$database['password']));
                        }

                        return $mongo->selectDatabase($database['dbname']);
                    },
                    true
                );

            } else {
                $this->di->set(
                    $key,
                    function () use ($database) {
                        return new DbAdapter((array)$database);
                    }
                );
            }
        }
        if($isMongo){
            $this->di->set(
                'collectionManager',
                function () {
                    $eventsManager = new Manager();
                    // Setting a default EventsManager
                    $modelsManager = new \Phalcon\Mvc\Collection\Manager();
                    $modelsManager->setEventsManager($eventsManager);
                    return $modelsManager;
                },
                true
            );
        }
    }

    public function hookEvents()
    {
        /**
         * Create a new Events Manager.
         */
        $eventsManager = new Manager();
        /**
         * Attach the middleware both to the events manager and the application
         */
        foreach ($this->di->getConfig()->events as $key => $events) {
            if(is_object($events)){
                foreach($events as $name => $event){
                    $eventsManager->attach($key, $this->di->getObjectManager()->get($event));
                }
            }

        }
        $this->di->setEventsManager($eventsManager);
        $this->setEventsManager($eventsManager);
    }

    public function registerRouters()
    {
        // Specify routes for modules
        $di = $this->di;
        $this->di->set(
            'router',
            function () use ($di) {
                $router = new Router();
                $router->setDefaultModule('core');
                $router->add(
                    '/:module/:controller/:action/:params',
                    [
                        'module' => 1,
                        'controller' => 2,
                        'action' => 3,
                        'params' => 4,
                    ]
                );
                foreach ($di->getConfig()->routers as $routes) {
                    $di->getObjectManager()->get($routes)->addRouter($router);
                }
                return $router;
            }
        );
        /* Register routes of other modules */
    }

    public function logException($msg, $type, $file)
    {
        try {
            $this->di->getLog()->logContent($msg, $type, $file);
            /*
             $transactions = $this->di->getTransactionManager()->getTransactions();
             foreach($transactions as $transaction){
                // $transaction->rollback('cant save');
             }
             */
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function run()
    {
        try {
            $response = $this->handle();
            return $response->send();
        } catch (\Phalcon\Mvc\Router\Exception $e) {
            $msg = $e->getMessage().PHP_EOL.$e->getTraceAsString();
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result = ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Mvc\Model\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Mvc\Application\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Logger\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Http\Response\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Http\Request\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Events\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Di\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Db\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Config\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Cache\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Application\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Acl\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Phalcon\Security\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        } catch (\Exception $e) {
            $msg = ($e->getMessage().PHP_EOL.$e->getTraceAsString());
            $this->logException($msg, Logger::CRITICAL, 'exception.log');
            $result =  ['success' => false, 'code' => 'something_went_wrong', 'message' => 'Something went wrong'];
        }
        return (new \Phalcon\Http\Response)->setJsonContent($result)->send();
    }
}
