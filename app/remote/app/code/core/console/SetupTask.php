<?php

use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class SetupTask extends Task
{
    public function upgradeAction()
    {
        
        $this->setActiveModules();
        foreach ($this->getAllModules() as $mod) {
            foreach ($mod as $module) {
                print_r($module);
                if ($module['active']) {
                    $className = "App\\" . ucfirst($module['name']) . "\Setup\UpgradeSchema";
                    if (class_exists($className)) {
                        
                        $this->di->getObjectManager()->get($className)->upgrade($this->di, $module['name'], $module['version']);
                        
                        
                    }

                    $className = "App\\" . ucfirst($module['name']) . "\Setup\UpgradeData";
                    if(class_exists($className)){
                        
                        $this->di->getObjectManager()->get($className)->upgrade($this->di, $module['name'], $module['version']);
                    }
                    foreach ($this->di->get('config')->databases as $key => $value) {
                        if($value->adapter == 'Mongo'){
                            continue;
                        }
                        $connection = $this->di->get($key);
                        $dbVersion = $connection->query("Select version From setup_module where module = '{$module['name']}'")->fetch();
                        if(isset($dbVersion['version'])){
                            if($module['version']>$dbVersion['version'])
                                $connection->query("Update setup_module set version = '{$module['version']}' where module = '{$module['name']}'");
                        }
                        else
                        {
                            $connection->query("Insert into setup_module (module, version) VALUES ('{$module['name']}', '{$module['version']}')");
                        }
                    }
                }
            }
        }
        echo 'The modules has been upgraded.' . PHP_EOL;
        echo 'Updating resources .' . PHP_EOL;
        $this->updateResourcesAction();
        echo 'Resources are updated .' . PHP_EOL;


        try{

            $connection = $this->di->get($this->di->getConfig()->default_db);
            $baseMongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');

            $roleResources = $baseMongo->getCollection('acl_role_resource')->find()->toArray();
            //print_r($roleResources);die;
            //$connection->query("select count(*) as count from acl_role_resource where 1")->fetch();



            if(count($roleResources) == 0){
                $collection = $baseMongo->getCollection('acl_role');
                $role = $collection->findOne(['code'=>'app']);

                $collection = $baseMongo->getCollection('acl_resource');
                $aclResources = $collection->find([
                    'controller'=>'user',
                    'module'=>'core',
                    "action" => [
                        '$in' => [
                            "login","forgot","forgotreset","create"
                        ]
                    ]


                ]);


                foreach($aclResources as $resource){
                    $aclRoleResource = $baseMongo->getCollection('acl_role_resource');
                    $aclRoleResource->insertOne(["role_id" => $role['_id'],"resource_id" => $resource['_id']]);

                }

            }

            echo 'Building Acl.' . PHP_EOL;
            $this->buildAclAction();
            echo 'Acl build completed.' . PHP_EOL;

        }catch (Exception $e){
          print_r($e->getMessage());
          die("Die hre");
        }

        
    }

    public function updateResourcesAction()
    {
        $finalResources = [];
        foreach ($this->getAllModules() as $mod) {
            foreach ($mod as $module) {
                foreach (glob(CODE . DS . $module['name'] . DS . 'controllers' . DS . '*.php') as $file) {

                    if ($file != CODE . DS . $module['name'] . DS . 'controllers' . DS . 'BaseController.php') {
                        // echo exec('php '.$file);
                        require $file;
                    }

                    $fileName = explode(DS, $file);
                    $fileName = $fileName[count($fileName) - 1];
                    list($className, $fileExtension) = explode('.', $fileName);
                    if($fileExtension != 'php'){
                        continue;
                    }
                    $moduleName = ucfirst($module['name']);
                    $class = "\App\\{$moduleName}\\Controllers\\{$className}";
                    $methods = get_class_methods($class);
                    $className = preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '-', $className);
                    $className = strtolower(str_replace('-Controller', '', $className));

                    foreach ($methods as $method) {
                        if (strpos($method, 'Action') !== false) {
                            $method = str_replace('Action', '', $method);
                            $resources = ['module' => $module['name'], 'controller' => $className, 'action' => $method];
                            $finalResources[$module['name'] . '_' . $className . '_' . $method] = 1;

                            if (!Resource::findFirst([$resources])
                            ) {

                                $resource = new Resource();
                                $resource->setData($resources);
                                $resource->save();
                                
                            }
                        }
                    }
                }
            }
        }

	$eventsManager = $this->di->getEventsManager();
        $eventsManager->fire('resource:updateAfter',
            $this,
            ['final_resources'=> &$finalResources]
        );
        if (!empty($finalResources)) {
            $allResources = Resource::find();
            foreach ($allResources as $resources) {
                if (!isset($finalResources[$resources->module . '_' . $resources->controller . '_' . $resources->action])) {
                    $resources->delete();
                }
            }
        }
    }

    public function buildAclAction()
    {
        $roles = Role::find();
        $acl = new \Phalcon\Acl\Adapter\Memory();
        $acl->setDefaultAction(\Phalcon\Acl\Enum::DENY);
        $resources = Resource::find();
        $components = [];
        foreach ($resources as $resource) {
            $components[$resource->module.'_'.$resource->controller][] = $resource->action;
        }
        foreach($components as $componentCode => $componentResources){
            $acl->addComponent($componentCode,$componentResources);
        }
        foreach ($roles as $role) {
            
            $acl->addRole($role->code);
            if ($role->resources == 'all') {
                foreach($components as $componentCode => $componentResources){
                    $acl->allow($role->code,$componentCode,'*');
                }

            } else {
                foreach ($role->getAllResources() as $roleResource) {
                    
                    
                    $_resource = Resource::findFirst([[ "_id" => $roleResource->resource_id]]);
                    if($_resource){
                        $acl->allow($role->code,$_resource->module.'_'.$_resource->controller,$_resource->action);
                    }
                    
                }
            }
        }

        $file = BP . DS . 'app' . DS . 'etc' . DS . 'security' . DS . 'acl.data';
        $handle = fopen($file, 'w+');
        fwrite($handle, serialize($acl));
        fclose($handle);
        $this->buildChildAcl();
    }


    public function buildChildAcl()
    {
        $subUsers = \App\Core\Models\User\SubUser::find();
        $helper = $this->di->getObjectManager()->get(\App\Core\Components\Helper::class);
        foreach ($subUsers as $subuser) {
            
            $helper->generateChildAcl($subuser->toArray());
            
        }

        
    }

    public function statusAction()
    {
        $filePath = BP . DS . 'app' . DS . 'etc' . DS . 'modules.php';
        $fileData = [];
        if (file_exists($filePath)) {
            $fileData = require $filePath;
        }
        echo var_export($fileData) . PHP_EOL;
    }

    public function enableAction($module)
    {
        if (isset($module[0])) {
            $file = BP . DS . 'app' . DS . 'code' . DS . $module[0] . DS . 'module.php';
            if (file_exists($file)) {
                $fileData = require $file;
                if (isset($fileData['active']) && $fileData['active'] == 0) {
                    $fileData['active'] = 1;
                    $handle = fopen($file, 'w+');
                    fwrite($handle, '<?php return ' . var_export($fileData, true) . ';');
                    fclose($handle);
                    $this->setActiveModules();
                    echo 'The modules has been enabled.' . PHP_EOL;
                } else {
                    echo 'This module is already enabled.' . PHP_EOL;
                }
            } else {
                echo 'Mentioned module does not exists.' . PHP_EOL;
            }
        } else {
            echo 'You did not mentioned the module name to be enabled.' . PHP_EOL;
        }
    }

    public function disableAction($module)
    {
        if (isset($module[0])) {
            $file = BP . DS . 'app' . DS . 'code' . DS . $module[0] . DS . 'module.php';
            if (file_exists($file)) {
                $fileData = require $file;
                if (isset($fileData['active']) && $fileData['active'] == 1) {
                    $fileData['active'] = 0;
                    $handle = fopen($file, 'w+');
                    fwrite($handle, '<?php return ' . var_export($fileData, true) . ';');
                    fclose($handle);
                    $this->setActiveModules();
                    echo 'The modules has been disabled.' . PHP_EOL;
                } else {
                    echo 'This module is already disabled.' . PHP_EOL;
                }
            } else {
                echo 'Mentioned module does not exists.' . PHP_EOL;
            }
        } else {
            echo 'You did not mentioned the module name to be disabled.' . PHP_EOL;
        }
    }

    protected function getAllModules()
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

    protected function setActiveModules()
    {
        $filePath = BP . DS . 'app' . DS . 'etc' . DS . 'modules.php';
        $activeModules = [];

        foreach ($this->getAllModules() as $mod) {
            foreach ($mod as $module) {
                $activeModules[$module['name']] = $module['active'];
            }
        }
        if (!file_exists(BP . DS . 'app' . DS . 'etc')) {
            $old = umask(0);
            mkdir(BP . DS . 'app' . DS . 'etc', 0777, true);
            umask($old);
        }

        $handle = fopen($filePath, 'w+');
        fwrite($handle, '<?php return ' . var_export($activeModules, true) . ';');
        fclose($handle);
        return $activeModules;
    }

    public function clearNotificationsAction() {
        $handlerData = [
            'type' => 'full_class',
            'class_name' => '\App\Core\Models\Notifications',
            'method' => 'clearSeenNotifications',
            'queue_name' => 'general',
            'own_weight' => 0,
            'data' => []
        ];
        if($this->di->getConfig()->enable_rabbitmq_internal){
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            $feedId = $helper->createQueue($handlerData['queue_name'],$handlerData);
        }
        return true;
    }

}
