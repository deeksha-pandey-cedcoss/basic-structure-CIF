<?php

namespace App\Core\Setup;

use \Phalcon\Di\DiInterface;

class UpgradeSchema extends Schema
{
    public function up(DiInterface $di, $moduleName, $currentVersion)
    {   

        $this->applyOnSingle($di, $moduleName, $currentVersion, 'db', function ($connection, $dbVersion) use ($di) {
            
            if ($dbVersion < '1.0.1') {

                $collection = $connection->selectCollection('acl_role');
                $collection->insertMany([
                    [
                        "code" => "admin",
                        "title" => "Administrator",
                        "description" => "ACL group allotted to administrators",
                        "resources" => "all"
                    ],
                    [
                        "code" => "app",
                        "title" => "app",
                        "description" => "ACL group allotted to app",
                        "resources" => ""
                    ],
                    [
                        "code" => "customer",
                        "title" => "customer",
                        "description" => "customer",
                        "resources" => ""
                    ],
                    [
                        "code" => "anonymous",
                        "title" => "anonymous",
                        "description" => "anonymous",
                        "resources" => "anonymous"
                    ],
                    [
                        "code" => "refresh",
                        "title" => "refresh",
                        "description" => "refresh",
                        "resources" => "refresh"
                    ]
                    
                ]);
                $collection->createIndex([ 'code' => 1 ], [ "unique" => true ]);

                $user = $di->getObjectManager()->create('\App\Core\Models\User');
                $user->createUser([
                        'username'=>'app',
                        'email'=>'ankurverma@cedcoss.com',
                        'password'=>'password123'
                    ], 'app');

                $collection = $connection->selectCollection('user');
                $collection->updateOne(["username" => "app"],['$set' => ["confirmation" => 1, "status" => 2]]);
               
                $data = $user->login([
                            'username'=>'app',
                            'email'=>'ankurverma@cedcoss.com',
                            'password'=>'password123'
                        ]);
                if ($data['success']) {
                    echo 'App Token : '.PHP_EOL.$data['data']['token'].PHP_EOL.PHP_EOL;
                } else {
                     echo PHP_EOL.$data['message'].PHP_EOL.PHP_EOL;
                }
                $user = $di->getObjectManager()->create('\App\Core\Models\User');
                $user->createUser([
                        'username'=>'admin',
                        'email'=>'satyaprakash@cedcoss.com',
                        'password'=>'password123'
                    ], 'admin');
                $collection->updateOne(["username" => "admin"],['$set' =>["confirmation" => 1, "status" => 2]]);
                $data = $user->login([
                        'username'=>'admin',
                        'email'=>'satyaprakash@cedcoss.com',
                        'password'=>'password123'
                    ]);
                if ($data['success']) {
                    echo 'Admin User : admin'.PHP_EOL.'Password: password123'.PHP_EOL.'Admin User Token : '.PHP_EOL.$data['data']['token'].PHP_EOL.PHP_EOL;
                } else {
                    echo PHP_EOL.$data['message'].PHP_EOL.PHP_EOL;
                }
                    

            }



            return true;
        });
    }

    /*
     *  create user_details collection in mongo and set the 2 user (app, admin) in that collection
    */
    private function createUserDetails(){
        $userDetails = $this->di->getObjectManager()->create('\App\Core\Models\User\Details');

        $userDetailsCollection = $userDetails->getCollection();
        $userDetailsCollection->createIndex(["_id"=> 1]);
        $userDetailsCollection->createIndex(["user_id"=> 1], ['unique' => true]);
        $userDetailsCollection->createIndex(["shops._id"=> 1]);
        $userDetailsCollection->createIndex([ "shops.warehouses._id"=> 1]);
    }
}
