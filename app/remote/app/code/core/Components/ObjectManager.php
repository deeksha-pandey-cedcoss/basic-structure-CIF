<?php

namespace App\Core\Components;

use Phalcon\Di\InjectionAwareInterface;

class ObjectManager extends Base
{

    public function create($type, array $args = [], $get = false)
    {
        
        $diType = $this->getDiClass($type);
        if(isset($args[0]) && is_array($args[0]) && isset($args[0]['type'])){
            $arguments = [];
            foreach($args as $argument ){
                if($argument['type']=='parameter'){
                    $arguments[] = $argument['value'];
                }
            }
        }
        else{
            $arguments = $args;
        }
        
        if (count($arguments)>0) {
            if (class_exists($diType)) {
                $reflection = new \ReflectionClass($diType);
                return $reflection->newInstanceArgs($arguments);
            } else {
                die('class does not exist');
            }
        } else {
            //echo $diType;die;
            //return $this->di->get($diType);
            return new $diType;
        }
        if (class_exists($diType)) {
            $reflection = new \ReflectionClass($diType);
        } elseif (interface_exists($diType)) {
            /* trying to create object for interface */
            die($diType.'=> Can not initialized');
        } else {
            /* interface/class not found */
            die($diType.'=>Does Not exist');
        }

        if (count($arguments)>0) {
            $instance = $reflection->newInstanceArgs($arguments);
        } else {
            $constructor = $reflection->getConstructor();
            if (is_null($constructor)) {
                /* if class is not having constructor */
                $instance = new $diType();
                //$instance = $$interceptorReflection->newInstanceArgs($arguments);
            } else {
                /* if class is having constructor */
                $parameters = $constructor->getParameters();
                $args = false;
                foreach ($parameters as $key => $param) {
                    if ($param->hasType()) {
                        $args = true;
                        $type = (string)$param->getType();
                        if (class_exists($type) || interface_exists($type)) {
                            /*creating constructor parameter object*/

                            if ($get) {
                                $arguments[$key] = $this->get($this->getDiClass($type));
                            } else {
                                $arguments[$key] = $this->create($this->getDiClass($type));
                            }
                        } else {
                            /*No default value found */
                            die('no default value found');
                        }
                    } else {
                        if ($param->isDefaultValueAvailable()) {
                            /* if default value of parameter is found*/
                            $arguments[$key] = $param->getDefaultValue();
                        }
                        /*
                        else{
                            die('Check the parameters in constructor');
                        }
                        */
                    }
                }
                if ($args) {
                    $instance = $reflection->newInstanceArgs($arguments);
                } else {
                    $instance = $reflection->newInstance();
                }
            }
        }
        return $instance;
    }

    public function get($type, array $arguments = [])
    {
        if (isset($this->di[$type])) {
            return $this->di->get($type);
        } else {
            $diClass = $this->getDiClass($type);
            
            if (empty($arguments)) {
                $instance = $this->di->get($diClass);
            } else {
                //$this->di->get($diClass);
                $this->di->set($type, ['className' => $diClass,'arguments'=> $arguments
                    ]);
                $instance = $this->di->get($diClass);
            }
            $this->di->setShared($type, $instance);
            
            return $instance;
        }
    }

    public function getDiClass($key)
    {
        $config = $this->getDi()->get('config');
        $diType = $config->get('di')->get($key);
        $diType = is_null($diType)?$key:$diType;
        return $diType;
    }
}
