
# Create Module

  - *Register Module*  
    Create Register.php in app/code/module-name/Register.php
    
    php
    <?php
    
    namespace App\Modulename;
    
    use Phalcon\Mvc\View;
    use Phalcon\DiInterface;
    use Phalcon\Mvc\Dispatcher;
    use Phalcon\Mvc\ModuleDefinitionInterface;
    
    class Register implements ModuleDefinitionInterface
    {
    
    /**
     * Register a specific autoloader for the module
     */
    public function registerAutoloaders(DiInterface $di = null){
    
    }
    
    /**
     * Register specific services for the module
     */
    public function registerServices(DiInterface $di){
        // Registering a dispatcher
        $di->set(
            'dispatcher',
            function () {
                $dispatcher = new Dispatcher();
                $dispatcher->setDefaultNamespace('App\Modulename\Controllers');
                return $dispatcher;
            }
        );
    
        // Registering the view component
        $di->set(
            'view',
            function () {
                $view = new View();
                $view->setViewsDir(CODE.'/modulename/views/');
                return $view;
            }
        );
      }
    }
    

*Define version and sort order:*

> Create module.php in app/code/module-name/module.php
> 
> php
> <?php
> return [
>   'name' => 'modulename',
>   'sort_order' => 3,
>   'active' => 1,
>   'version' => '0.0.1'
> ];
> 
> 
> *active* is for enabling or disabling the module

  - *Create Composer Json*  
    Create composer.json in app/code/module-name/composer.json
    
    json
    { 
     "name": "cedcoss/phalcon-module-modulename", 
     "description": "Module description", 
     "require": { 
       "php": ">=5.6",
       "phpmailer/phpmailer": "^6.0"
     },
     "extra": {
       "module": "modulename"
     },
     "type": "ced-phalcon", 
     "version": "module-version", 
     "license": [ "EULA" ] 
    }
    
