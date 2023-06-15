<?php

namespace App\Core;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;
use Phalcon\Logger;
use Phalcon\Mvc\Router;

class Application extends \Phalcon\Mvc\Application
{
    use Traits\Application;
    /**
     * Application Constructor
     *
     * @param \Phalcon\Di\DiInterface $di
     */
    public function __construct(DiInterface $di = null)
    {
        /**
         * Sets the parent DI and register the app itself as a service,
         * necessary for redirecting HMVC requests
         */
        parent::setDI($di);
        $di->set('app', $this);
        $this->di->get('\App\Core\Components\AppCode');
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
        $this->di->getUrl()->setBaseUri($this->di->getConfig()->backend_base_url);
        $this->di->setTokenManager($this->di->getObjectManager()->get('App\Core\Components\TokenManager'));
        $this->hookEvents();
        $this->registerRouters();
    }

    public function registerDi(){
        foreach ($this->di->getConfig()->di as $key => $class) {
            $this->di->getObjectManager()->get($key);
        }
    }

    

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
        } catch (\Phalcon\Mvc\Model\Transaction\Failed $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function run()
    {
        try {

            $this->router->setDefaultAction('index');
            $this->router->setDefaultController('index');
            $str = str_replace($this->di->getConfig()->get('base_path_removal'), '', $_SERVER['REQUEST_URI']);
            $response = $this->handle($str);
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
