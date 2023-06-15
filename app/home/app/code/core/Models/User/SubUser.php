<?php

namespace App\Core\Models\User;

use Phalcon\Mvc\Model\Query;

class SubUser extends \App\Core\Models\BaseMongo
{
    protected $table = 'sub_user';
    protected $isGlobal = true;
    // protected $resources;

    private $_collection;

    const IS_EQUAL_TO = 1;
    const IS_NOT_EQUAL_TO = 2;
    const IS_CONTAINS = 3;
    const IS_NOT_CONTAINS = 4;
    const START_FROM = 5;
    const END_FROM = 6;
    const RANGE = 7;

    public function initialize()
    {

        $this->setSource($this->table);

        $this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
        //$this->setReadConnectionService('dbSlave');

        //$this->setWriteConnectionService('dbMaster');
    }

    public function init() {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $this->_collection = $mongo->getCollectionForTable("sub_user");
    }

    public function createUser($data, $resgistration = false,$autoCreate = false)
    {
        $this->init();
        $errors = false;
        if (isset($data['username']) && isset($data['email']) && isset($data['password'])) {
            if ($this->_collection->findOne(['username'=>$data['username']])) {
                $errors[] = 'Username already exist.';
            }
            /*if ($this->_collection->findOne(['email'=>$data['email']])) {
                $errors[] = 'Email already exist.';
            }*/
            if (!$errors) {
                $user = $this->di->getUser();
                $data['password'] = $user->getHash($data['password']);

                if ( $resgistration ) {
                    $user = \App\Core\Models\User::findFirst([["username"=>"admin"]]);
                    $role = \App\Core\Models\Acl\Role::findFirst([["code"=>"admin"]]);
                    $data['role_id'] = (string)$role->getId();
                    $data['parent_id'] = (string)$user->getId();;
                } else {
                    $data['role_id'] = $user->role_id;
                    $data['parent_id'] = $user->id;
                }
                 
                if (isset($data['resources']) && $data['resources']) {
                    $Role = \App\Core\Models\Acl\Role::findFirst([["_id"=>$user->role_id]]);

                    if ( $resgistration ) {
                        $data['resources'] = array_values($data['resources']);
                    } else if ($Role->resources != 'all') {
                        $all_resources = \App\Core\Models\Acl\RoleResource::find([["role_id" => $user->role_id]]);
                        $resources = [];
                        foreach ($all_resources as $resource) {
                            $resources[] = $resource['resource_id'];
                        }
                        $data['resources'] = array_values(array_intersect($resources, $data['resources']));
                    }
                } else {
                    $data['resources'] = 'all';
                }
                $data['created_at'] = date('m-d-Y h:i:s a', time());
                if ( isset($data['apps']) ) {
                    foreach ( $data['apps'] as $app_key => $app_val ) {
                        $data['apps'][$app_key]['_id'] = $this->getCounter('user_app_id');
                        $helper = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
                        $keys = $helper->getKeyPair();
                        $data['apps'][$app_key]['public_key'] = $keys['public'];
                        $data['apps'][$app_key]['private_key'] = $keys['private'];
                    }
                }
                $data['_id'] = $this->getCounter('sub_user_id');;
                if($autoCreate) {
                    $data['confirmation'] = 0;
                } else {
                    $data['confirmation'] = 1;
                }
                if ($this->_collection->insertOne($data)) {
                    $this->sendWelcomeMail($data);

                    $helper = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
                    $helper->generateChildAcl($data);
                    if($autoCreate) {
                        $this->sendConfirmationMail($data);
                        $result = [
                            'success' => true,
                            'code' => 'confirmation sent',
                            'message' => 'Check your mail to verify your account.',
                            'data' => $data
                        ];

                    } else {
                        $result = ['success' => true, 'code' => 'notification_send', 'message' => 'Notification mail has been send to user', 'data' => []];
                    }
                } else {
                    $messages = $this->getMessages();
                    $errors = [];
                    foreach ($messages as $message) {
                        $errors[] = (string)$message;
                    }
                    $result = ['success' => false, 'code' => 'unknown_error', 'message' => 'Something went wrong', 'data' => $errors];
                }
            } else {
                $result = ['success' => false, 'code' => 'user_already_exist', 'message' => 'Duplicate', 'data' => ['errors' => $errors]];
            }
        } else {
            $result = ['success' => false, 'code' => 'data_missing', 'message' => 'Fill All required fields.', 'data' => []];
        }
        return $result;
    }
    
    public function sendConfirmationMail($data)
    {
        if (isset($data['confirmation_link'])) {
//            print_r($data);
            $data['confirmation_link'] = "https://apiconnect.sellernext.com/apiconnect/uservalidate/validate";
            $email = $data['email'];
            $date1 = new \DateTime('+24 hour');
            $token = [
                "user_id" => 2,
                "child_id" => $data['_id'],
                "exp" => $date1->getTimestamp()
            ];
            $token = $this
                ->di
                ->getObjectManager()
                ->get('App\Core\Components\Helper')
                ->getJwtToken($token, 'HS256', true, true, false);
                
            $link = $data['confirmation_link'].'?token=' . $token;
            $path = 'core' . DS . 'view' . DS . 'email' . DS . 'userconfirm.volt';
            $data['email'] = $email;
            $data['path'] = $path;
            $data['link'] = $link;
            $data['subject'] = 'Account Confirmation Mail';
            $res = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
            
        }
    }
    public function updateSubUser($data)
    {
        $this->init();
        $user = $this->di->getUser();
        $subUser = $this->_collection->findOne([
            '$or' => [
                ['_id'=> $data['id']],
                ['_id'=> (int)$data['id']],
                ['_id'=> (string)$data['id']],
            ]
        ]);
        if (count($subUser) > 0) {
            if ($subUser['parent_id'] == $user->id) {
                if (isset($data['password']) && $data['password']) {
                    $data['password'] = $user->getHash($data['password']);
                    $this->_collection->updateOne(['_id' => $subUser['_id']],
                        ['$set' => [
                            'password' => $data['password'],
                        ]]);
                }
                $helper = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
                if (isset($data['resources']) && $data['resources']) {
                   $Role = \App\Core\Models\Acl\Role::findFirst([["_id"=>$user->role_id]]);

                    /*if ($Role->resources != 'all') {*/
                        $all_resources = \App\Core\Models\Acl\RoleResource::find([["role_id" => $user->role_id]])->toArray();


                        foreach ($all_resources as $resource) {
                            $resources[] = $resource['resource_id'];
                        }
                        if ($Role->resources != 'all'){
                            $data['resources'] = array_intersect($resources, $data['resources']);
                        }


                        $this->_collection->updateOne(['_id' => $subUser['_id']],
                            ['$set' => [
                                'resources' => $data['resources']
                            ]]);
                    }

                if ( isset($data['apps']) ) {
                    $userApps = [];
                    foreach ( $subUser['apps'] as $key =>  $value ) {
                        $userApps[$value['app_id']] = $value;

                    }

                    foreach ( $data['apps'] as $key1 => $val2 ) {
                        if ( isset($userApps[$val2['app_id']])  ) {
                            $value = $userApps[$val2['app_id']];
                            $data['apps'][$key1]['_id'] = $value['_id'];

                            $newApp = false;
                            if(!isset($value['public_key'])){
                                $helper = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
                                $keys = $helper->getKeyPair();
                                $data['apps'][$key1]['public_key'] = $keys['public'];
                                $data['apps'][$key1]['private_key'] = $keys['private'];
                            }
                            else{
                                $data['apps'][$key1]['public_key'] = $value['public_key'];
                                $data['apps'][$key1]['private_key'] = $value['private_key'];
                            }

                        }
                        else{
                            $data['apps'][$key1]['_id'] = $this->getCounter('user_app_id');

                            $keys = $helper->getKeyPair();
                            $data['apps'][$key1]['public_key'] = $keys['public'];
                            $data['apps'][$key1]['private_key'] = $keys['private'];
                        }
                    }
                    $this->_collection->updateOne(['_id' => $subUser['_id']],
                        ['$set' => [
                            'apps' => $data['apps'],
                        ]]);
                }
                $helper->generateChildAcl($subUser);
                return ['success' => true, 'message' => 'User Details updated successfully.'];
            } else {
                return ['success' => false, 'code' => 'not_allowed', 'message' => 'You are not allowed to updated the sub user'];
            }
        } else {
            return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No Customer Found'];
        }
    }

    public function updateSubUserApp($data) {
        $this->init();
        $sub_user = $this->di->getSubUser();
        if ( count($sub_user) ) {
            $this->_collection->updateOne(
                ['username'=>$sub_user['username']],
                ['$set' => ['apps' => $data['apps']]]
            );
            return ['success' => true, 'message' => 'Auth Url Successfully Updated'];
        } else {
            return ['success' => false, 'message' => 'Sub User Not Found'];

        }
    }

    public function deleteSubUser($data)
    {
        $this->init();
        $current_user = $this->di->getUser();
        if (isset($data['id'])) {
            $user = $this->_collection->deleteOne(['parent_id' => $current_user->id,'id'=>$data['id']]);
        } elseif (isset($data['username'])) {
            $user = $this->_collection->deleteOne(['parent_id' => $current_user->id,'username'=>$data['username']]);
        } elseif (isset($data['email'])) {
            $user = $this->_collection->deleteOne(['parent_id' => $current_user->id,'email'=>$data['email']]);
        }
        if ($user->getDeletedCount() > 0) {
            return ['success' => true, 'message' => 'User has been deleted'];
        } else {
            return ['success' => false, 'code' => 'no_customer_found', 'message' => 'User not found'];
        }
    }

    public function login($data)
    {
        $this->init();
        $user = new \App\Core\Models\User;
        if (isset($data['username']) && isset($data['password'])) {
            $subUser = $this->_collection->findOne(['username'=>$data['username']]);
            if (count($subUser) > 0) {
                if ($user->checkHash($data['password'], $subUser['password'])) {
                    return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $this->getToken($subUser)]];
                }
            }
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid username or password', 'data' => []];
        } elseif (isset($data['email']) && isset($data['password'])) {
            $subUser = $this->_collection->findOne(['email'=>$data['email']]);
            if (count($subUser) > 0) {
                if ($user->checkHash($data['password'], $subUser['password'])) {
                    return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $this->getToken($subUser)]];
                }
            }
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid username or password', 'data' => []];
        }
        return ['success' => false, 'code' => 'data_missing', 'message' => 'Fill All Required Fields..', 'data' => []];
    }

    public static function search($filterParams = [], $fullTextSearchColumns = null)
    {
        $conditions = [];
        if (isset($filterParams['filter'])) {
            foreach ($filterParams['filter'] as $key => $value) {
                $key = trim($key);
                if (array_key_exists(SubUser::IS_EQUAL_TO, $value)) {
                    $conditions[] = "" . $key . " = '" . trim(addslashes($value[SubUser::IS_EQUAL_TO])) . "'";
                } elseif (array_key_exists(SubUser::IS_NOT_EQUAL_TO, $value)) {
                    $conditions[] = "" . $key . " != '" . trim(addslashes($value[SubUser::IS_NOT_EQUAL_TO])) . "'";
                } elseif (array_key_exists(SubUser::IS_CONTAINS, $value)) {
                    $conditions[] = "" . $key . " LIKE '%" . trim(addslashes($value[SubUser::IS_CONTAINS])) . "%'";
                } elseif (array_key_exists(SubUser::IS_NOT_CONTAINS, $value)) {
                    $conditions[] = "" . $key . " NOT LIKE '%" . trim(addslashes($value[SubUser::IS_NOT_CONTAINS])) . "%'";
                } elseif (array_key_exists(SubUser::START_FROM, $value)) {
                    $conditions[] = "" . $key . " LIKE '" . trim(addslashes($value[SubUser::START_FROM])) . "%'";
                } elseif (array_key_exists(SubUser::END_FROM, $value)) {
                    $conditions[] = "" . $key . " LIKE '%" . trim(addslashes($value[SubUser::END_FROM])) . "'";
                } elseif (array_key_exists(SubUser::RANGE, $value)) {
                    if (trim($value[SubUser::RANGE]['from']) && !trim($value[SubUser::RANGE]['to'])) {
                        $conditions[] = "" . $key . " >= '" . $value[SubUser::RANGE]['from'] . "'";
                    } elseif (trim($value[SubUser::RANGE]['to']) && !trim($value[SubUser::RANGE]['from'])) {
                        $conditions[] = "" . $key . " >= '" . $value[User::RANGE]['to'] . "'";
                    } else {
                        $conditions[] = "" . $key . " between '" . $value[User::RANGE]['from'] . "' AND '" . $value[SubUser::RANGE]['to'] . "'";
                    }
                }
            }
        }
        if (isset($filterParams['search']) && $fullTextSearchColumns) {
            $conditions[] = " MATCH (" . $fullTextSearchColumns . ") AGAINST ('" . trim(addslashes($filterParams['search'])) . "' IN NATURAL LANGUAGE MODE)";
        }
        $conditionalQuery = "";
        if (is_array($conditions) && count($conditions)) {
            $conditionalQuery = implode(' AND ', $conditions);
        }
        return $conditionalQuery;
    }

    public function getSubUser($data, $limit = 1, $activePage = 1, $filters = [])
    {
        $this->init();
        if ($data && isset($data['id'])) {
            $content = $this->_collection->findOne(['_id'=> (string)$data['id']]);
            return ['success' => true, 'message' => '', 'data' => $content];
        } else {
            $user = $this->di->getUser();
            if (count($filters) > 0) {
//                $fullTextSearchColumns = "`username`,`email`";
//                $query = 'SELECT * FROM \App\Core\Models\User\SubUser WHERE parent_id = ' . $user->id . ' AND ';
//                $countQuery = 'SELECT COUNT(*) FROM \App\Core\Models\User\SubUser WHERE parent_id = ' . $user->id . ' AND ';
//                $conditionalQuery = self::search($filters, $fullTextSearchColumns);
//                $query .= $conditionalQuery;
//                $countQuery .= $conditionalQuery . ' LIMIT ' . $limit . ' OFFSET ' . $activePage ;
//                $exeQuery = new Query(
//                    $query,
//                    $this->di
//                );
//                $collection = $exeQuery->execute();
//                $exeCountQuery = new Query(
//                    $countQuery,
//                    $this->di
//                );
//                $collectionCount = $exeCountQuery->execute();
//                $collectionCount = $collectionCount->toArray();
//                $collectionCount[0] = json_decode(json_encode($collectionCount[0]), true);
//                return ['success' => true, 'message' => '', 'data' => ['rows' => $collection, 'count' => $collectionCount[0][0]]];
            } else {
                $content = $this->_collection->find(["parent_id"=> $user->id ], [ 'limit' => (int)$limit, 'skip' => (int)(($activePage) * $limit)])->toArray();
                $count = $this->_collection->count(["parent_id"=>$user->id]);
                return ['success' => true, 'message' => '', 'data' => ['rows' => $content, 'count' => $count]];
            }
        }
    }

    public function getToken($subUser)
    {
        $date = new \DateTime('+4 hour');
        if ($this->getRole()->code == 'app') {
            $token = [
                "user_id" => $subUser['parent_id'],
                "child_id" => $subUser['_id'],
                "role" => $this->getRole()->code
            ];
        } else {
            $token = [
                "user_id" => $subUser['parent_id'],
                "child_id" => $subUser['_id'],
                "role" => $this->getRole()->code,
                "exp" => $date->getTimestamp()
            ];
        }
        $token = [
            "user_id" => $subUser['parent_id'],
            "child_id" => $subUser['_id'],
            "role" => 'admin',
            "exp" => $date->getTimestamp()
        ];
        return $this->di->getObjectManager()->get('App\Core\Components\Helper')->getJwtToken($token);
    }

    public function getRole()
    {
        $user = $this->di->getUser();
        return \App\Core\Models\Acl\Role::findFirst([["_id"=>$user->role_id]]);
    }

    public function sendWelcomeMail($data)
    {
        if (isset($data['confirmation_link'])) {
            $path = 'core' . DS . 'view' . DS . 'email' . DS . 'subuserwelcome.volt';
            $data['path'] = $path;
            $data['subject'] = 'New User Welcome Mail';
            $token = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
        }
    }

    public function setResources($resources)
    {
        if($resources=='all'){
            $this->resources = 'all';
        }
        else{
            if (!is_array($resources)) {
                throw new InvalidArgumentException(
                    'Resources should be array of resource ids'
                );
            }
            // $this->resources = json_encode(array_values($resources));
            $this->resources = $resources;
        }

    }

    public function getResources()
    {
        if($this->resources=='all'){
            return 'all';
        }
        else{
            if(is_array($this->resources)){
                return $this->resources;
            }
            return json_decode($this->resources);
        }

    }
}
