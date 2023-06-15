<?php

namespace App\Core\Components;

use \Phalcon\Config;
use \Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use \Phalcon\Di\DiInterface;
use \Phalcon\Events\Manager;
use \Phalcon\DI;
use \Phalcon\Di\FactoryDefault,
    \Phalcon\Loader;
class UnitTestApp extends \Phalcon\Test\UnitTestCase
{
    /**
     * @var bool
     */
    private $_loaded = false;

    public function testAliExpress($sqs = [])
    {
        print_r($sqs);die;
    }

    public function setUp(): void
    {
        $di = new FactoryDefault();
        require BP.DS.'vendor'.DS.'autoload.php';

        /**Register loader for modules**/
        $di->set(
            'loader',
            function () {
                $loader = new Loader();
                return $loader;
            }
        );

        $loader = $di['loader'];
        $loader->registerNamespaces(
            [
                'Phalcon' => BP.DS.'vendor'.DS.'phalcon'.DS.'incubator'.DS.'Phalcon'.DS,
                'App\Core'   => CODE.DS.'core',
                'App\Core\Middlewares'   => CODE.DS.'core'.DS.'Middlewares'.DS,
            ]
        );

        $loader->register();

// Create an application
        $application = new \App\Core\UnitApplication($di);


        $this->setDi($di);
         $this->_loaded = true;
        
    }



    
}
