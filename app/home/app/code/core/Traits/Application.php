<?php 
namespace App\Core\Traits;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;

trait Application{
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
                    'App\\' . ucfirst($moduleName) . '\Traits' => CODE . DS . $moduleName . DS . 'Traits' . DS

                ];

                $namespaces = array_merge($namespaces, $namespace);

                $modules[$moduleName] = [
                    'className' => 'App\\' . ucwords($moduleName) . '\Register',
                    'path' => CODE . DS . $moduleName . DS . 'Register.php',
                ];
            }
            $directories[] = CODE.DS.$moduleName.DS.'console';

        }
        $loader->registerDirs($directories);
        $loader->registerNamespaces($namespaces);
        $loader->register();
        $this->registerModules($modules);
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
                    $templatePath = CODE . DS . $module . DS . 'etc' . DS . 'template.php';
                    if (file_exists($templatePath)) {
                        $array = new \Phalcon\Config\Adapter\Php($templatePath);
                        $config->merge($array);
                    }
                }
            }
            $filePath = BP . DS . 'app' . DS . 'etc' . DS . 'config.php';
            if (file_exists($filePath)) {
                $array = new \Phalcon\Config\Adapter\Php($filePath);
                $config->merge($array);
            }

            if( $parameterName = $config->get('swa_p_n') ){
                $ssmClient = new \Aws\Ssm\SsmClient(include BP . '/app/etc/aws.php');
                $result = $ssmClient->getParameter([
                    'Name' => $parameterName, // REQUIRED
                    'WithDecryption' => true,
                ])->toArray();
                //print_r($result);die;
                if(isset($result['Parameter'],$result['Parameter']['Value'])){
                    $conf = json_decode($result['Parameter']['Value'],true);
                }
                else{
                    $conf = [];
                }
                $config->merge(new \Phalcon\Config($conf));
            }

           
            
            $this->di->getCache()->set('config', $config);

        }
        $this->di->set('config', $config);
    }



    public function loadDatabase()
    {
        if( method_exists($this->di, 'getRequest')){
            // for frontend requests
            $isReadPreferred = $this->di->getRequest()->getHeader('Connection-IsReadPreferred') ? true : false;
        }
        else{
            // for console requests
            $isReadPreferred = false;
        }
        $isMongo = false;

        foreach ($this->di->getConfig()->databases as $key => $database) {
            if ($database['adapter'] == 'Mongo') {
                $isMongo = true;
                $this->di->set(
                    $key,
                    function () use ($database,$isReadPreferred) {
                        if (!isset($database['username'])) {
                            $dsn =  $database['host'];
                            $mongo = new \MongoDB\Client($dsn);
                        } else {
                    
                            $dsn =  $database['host'];
                            if($isReadPreferred){
                                
                                $dsn =  $database['read-host'] ?? $database['host'];
                            }
                            
                            $mongo = new \MongoDB\Client($dsn,array("username" => $database['username'], "password" =>$database['password']));
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
        if( $isMongo ){
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

            if( ( php_sapi_name() === 'cli' && $key!='application:beforeHandleRequest' && is_object($events)) || 
                ( php_sapi_name() !== 'cli' && is_object($events) )){
                foreach($events as $name => $event){
                    $eventsManager->attach($key, $this->di->getObjectManager()->get($event));
                }
            }

        }
        $this->di->setEventsManager($eventsManager);
        $this->setEventsManager($eventsManager);
    }

}