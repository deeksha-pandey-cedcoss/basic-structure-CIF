<?php

namespace App\Core\Models;

use Phalcon\Security;
use \Firebase\JWT\JWT;
use Phalcon\Mvc\Model\Query;

class User extends BaseMongo
{
    protected $table = 'user_details';

    public $status = ['1' => 'Under Review', '2' => 'Active', '3' => 'Inactive'];

    const IS_EQUAL_TO = 1;
    const IS_NOT_EQUAL_TO = 2;
    const IS_CONTAINS = 3;
    const IS_NOT_CONTAINS = 4;
    const START_FROM = 5;
    const END_FROM = 6;
    const RANGE = 7;
    public $sqlConfig;
    protected $isGlobal = true;

   

    public function createUser($data, $type = 'customer', $autoConfirm = false)
    {
        
        $errors = false;
        if (isset($data['username']) && isset($data['email']) && isset($data['password'])) {
            /*if (User::findFirst("email='{$data['email']}'")) {
                $errors[] = 'Email already exist.';
            }*/

            
            $user = $this->findFirst([["username" => $data['username']]]);
            
            if ($user && isset($user->_id)) {
                $errors[] = 'Username already exist.';
            }


            if (!$errors) {
                $data['password'] = $this->getHash($data['password']);
               
                if ($type == 'customer') {
                    $roleId = (Acl\Role::findFirst([["code"=>"customer"]]))->_id;
                } elseif ($type == 'app') {
                    $roleId = (Acl\Role::findFirst([["code"=>"app"]]))->_id;
                } elseif ($type == 'admin') {
                    $roleId = (Acl\Role::findFirst([["code"=>"admin"]]))->_id;
                }
                $data['role_id'] = $roleId;
                $coreConfig = $this->di->getCoreConfig();
                
                $data['confirmation'] = 1;
                $data['status'] = 2;
               

                // TODO required

                // if ($coreConfig->get('customer/create_account/confirm')) {
                //     $data['confirmation'] = 0;
                //     if ($autoConfirm) {
                //         $data['confirmation'] = 1;
                //     }
                // } else {
                //     $data['confirmation'] = 1;
                // }

                // if ($coreConfig->get('customer/create_account/approval')) {
                //     $data['status'] = 1;
                //     if ($autoConfirm) {
                //         $data['status'] = 2;
                //     }
                // } else {
                //     $data['status'] = 2;
                // }
                $data['created_at'] = new \MongoDB\BSON\UTCDateTime();
                
                if ($this->setData($data)->save()) {
                    $this->setUserId((string)$this->_id);
                    $this->save();
                    if ($data['confirmation']) {
                        $result = ['success' => true, 'code' => 'account_created', 'message' => 'Customer created successfully', 'data' => []];
                    } else {
                        $this->sendConfirmationMail($data);
                        $result = ['success' => true, 'code' => 'confirmation_required', 'message' => 'Check your mail to verify your account.', 'data' => []];
                    }
                    $eventsManager = $this->di->getEventsManager();
                    $eventsManager->fire('application:createAfter', $this);
                } else {
                    $messages = $this->getMessages();
                    $errors = [];
                    foreach ($messages as $message) {
                        $errors[] = (string)$message;
                    }
                    $result = ['success' => false, 'message' => 'Something went wrong', 'data' => $errors];
                }
                
            } else {
                $result = ['success' => false, 'code' => 'user_already_exist', 'message' => 'User already exists', 'data' => ['errors' => $errors]];
            }
        } else {
            $result = ['success' => false, 'code' => 'data_missing', 'message' => 'Fill All required fields.', 'data' => []];
        }
        return $result;
    }

    public function getHash($password)
    {
        return md5($this->getSaltedString($password));
    }

    public function getSaltedString($string)
    {
        $pepper = 'cedcommerce';
        $salt = 'connector';
        return $pepper . $string . $salt;
    }

    public function checkPassword($password, $dbPassword = false)
    {
        if (!$dbPassword) {
            return $this->checkHash($password, $this->password);
        }
        return $this->checkHash($password, $dbPassword);
    }

    public function checkHash($password, $dbPassword)
    {
        return $this->getHash($password) === $dbPassword;
    }

    /**
     * @param $data                                                    
     * @return array
     */
    public function login($data)
    {
        if (isset($data['username']) && isset($data['password'])) {
            $user = User::findFirst([["username"=>$data['username']]]);
            if ($user) {
                if (!property_exists($user, 'confirmation') || $user->confirmation == 0) {

                    return ['success' => false, 'message' => 'Check your mail to confirm your account.', 'code' => 'account_not_confirmed', 'data' => []];
                } else {
                    if ($user->status == 1) {
                        return ['success' => false, 'message' => 'Your account is under review', 'code' => 'staus_under_review', 'data' => []];
                    }
                    if ($user->status == 3) {
                        return ['success' => false, 'message' => 'Your account has been dectivate', 'code' => 'staus_not_active', 'data' => []];
                    }
                    if ($user->checkPassword($data['password'])) {
                        return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $user->getToken()]];
                    } elseif ($this->allowProxyLogin($user->_id)) {
                        return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $user->getToken()]];
                    }
                }
            }
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid username or password', 'data' => []];
        } elseif (isset($data['email']) && isset($data['password'])) {
            $user = User::findFirst([["email"=> $data['email']]]);
            if ($user) {
                if ($user->confirmation == 0) {
                    return ['success' => false, 'message' => 'Account is not confirmed yet', 'code' => 'account_not_confirmed', 'data' => []];
                } else {
                    if ($user->status == 1) {
                        return ['success' => false, 'message' => 'Your account is under review', 'code' => 'staus_under_review', 'data' => []];
                    }
                    if ($user->status == 3) {
                        return ['success' => false, 'message' => 'Your account has been dectivate', 'code' => 'staus_not_active', 'data' => []];
                    }
                    if ($user->checkPassword($data['password'])) {
                        return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $user->getToken()]];
                    } elseif ($this->allowProxyLogin($user->id)) {
                        return ['success' => true, 'message' => 'User logged in', 'data' => ['token' => $user->getToken()]];
                    }
                }
            }
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid email or password', 'data' => []];
        }
        return ['success' => false, 'code' => 'data_missing', 'message' => 'Fill All Required Fields..', 'data' => []];
    }

    public function allowProxyLogin($userId)
    {
        $proxyUserId = -1;
        return $proxyUserId == $userId;
    }

    public function getToken($time = '+4 hour',$additionalData = false, $restrictForSameUser = true )
    {
        
        $date1 = new \DateTime($time);
        if ($this->getRole()->code == 'app') {
            $token = [
                "user_id" => (string)$this->_id,
                "role" => $this->getRole()->code
            ];
        } else {
            $token = [
                "user_id" => (string)$this->_id,
                "role" => $this->getRole()->code,
                "exp" => $date1->getTimestamp()
            ];
        }
        if ($additionalData ) {
            $token = array_merge($token, $additionalData);
        }
        // $sd = new \App\Core\Components\Helper();
        // die($sd->getJwtToken($token));

        $this->di = $this->getDi();
        
        return $this
            ->di
            ->getObjectManager()
            ->get('\App\Core\Components\Helper')
            ->getJwtToken($token, 'RS256', true, false, $restrictForSameUser);
    }

    public function getRole()
    {
        return Acl\Role::findFirst([["_id"=>$this->role_id]]);
    }

    public function deleteUser($data)
    {
        if (isset($data['id'])) {
            $user = User::findFirst([["_id" => $data['id']]]);
        } elseif (isset($data['username'])) {
            $user = User::findFirst(['username' => $data['username']]);
        } elseif (isset($data['email'])) {
            $user = User::findFirst(["email"=>$data['email']]);
        }
        if ($user) {
            $userdb = UserDb::findFirst([ "_id" => (string)$user->id ]);
            
            $userdb->delete();
            
            return ['success' => true, 'message' => 'User has been deleted'];
        }
        return ['success' => false, 'code' => 'no_customer_found', 'message' => 'User not found'];
    }

    public function resetUserPassword($data)
    {
        if (isset($data['old_password']) && isset($data['new_password'])) {
            if ($this->checkPassword($data['old_password'])) {
                $password = $this->getHash($data['new_password']);
                $this->save(['password' => $password]);
                return ['success' => true, 'message' => 'Password reseted successfully'];
            } else {
                return ['success' => false, 'code' => 'password_not_matched', 'message' => 'Old Password not matched'];
            }
        } else {
            return ['success' => false, 'code' => 'data_missing', 'message' => 'Request data missing'];
        }
    }

    public function sendConfirmationMail($data)
    {   
        if (isset($data['confirmation_link'])) {
            $email = $this->email;
            $date1 = new \DateTime('+24 hour');
            $token = array(
                "user_id" => $this->id,
                "exp" => $date1->getTimestamp()
            );
            $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->getJwtToken($token, 'HS256');
            $link = $data['confirmation_link'] .'?token='. $token;
            $path = 'core' . DS . 'view' . DS . 'email' . DS . 'userconfirm.volt';
            $data['email'] = $email;
            $data['path'] = $path;
            $data['link'] = $link;
            $data['subject'] = 'Account Confirmation Mail';
            $token = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
        }
    }

    public function sendAcknowledgementMail($data)
    {
        $path = 'core' . DS . 'view' . DS . 'email' . DS . 'acknowledgement.volt';
        $data['email'] = $data['email'];
        $data['username'] = $data['username'];
        $data['text'] = $data['text'];
        $data['subject'] = $data['subject'];
        $data['path'] = $path;
        $token = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
    }

    public function sendReportIssueMail($data)
    {
        $path = 'core' . DS . 'view' . DS . 'email' . DS . 'reportissue.volt';
        $data['email'] = $data['email'];
        $data['text'] = $data['text'];
        $data['subject'] = $data['subject'];
        $data['path'] = $path;
        $token = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
    }

    public function reportIssue($issueDetails) {
        $userId = $this->di->getUser()->id;
        $subject = $issueDetails['subject'];
        $body = $issueDetails['body'];
        $userDetails = User::findFirst(["id='{$userId}'"]);
        $userEmail = $userDetails->email;
        $username = $userDetails->username;
        $ourEmail = 'sudeepmukherjee@cedcoss.com';
        $acknowledgementMailData = [
            'email' => $userEmail,
            'username' => $username,
            'title' => $subject,
            'text' => 'Sorry for the incovenience. We have recieved your issue of "' . $body . '". We are working hard to fix it. Your issue is our priority.',
            'body' => $body,
            'subject' => 'Sorry for the incovenience'
        ];
        $reportIssueMailData = [
            'email' => $ourEmail,
            'subject' => $subject,
            'text' => 'We have recieved an issue from MID -> ' . $userId . ', Username -> ' . $username . ', Email -> ' . $userEmail . '.and Issue is => ' . $body . '. Subject of issue => ' . $subject . '.'
        ];
        $this->sendAcknowledgementMail($acknowledgementMailData);
        $this->sendReportIssueMail($reportIssueMailData);
        return ['success' => true, 'message' => 'We have recieved your issue. We will come up with a solution soon. Our support team will contact you soon.'];
    }

    public function confirmUser($token)
    {
        $decoded = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($token);
        if ($decoded['success'] && $decoded['data']['user_id']) {
            $user = User::findFirst("id='{$decoded['data']['user_id']}'");
            if ($user) {
                $user->save(['confirmation' => 1, 'status' => 2]);
                return ['success' => true, 'message' => 'User Verified Successfully.'];
            } else {
                return ['success' => false, 'code' => 'no_customer_found', 'message' => 'User not found.'];
            }
        } else {
            if ($decoded['code'] == 'token_expired') {
                $decoded['code'] = 'confirmation_token_expired';
            }
            return $decoded;
        }
    }

    public function forgotPassword($data)
    {
        if (isset($data['email']) && $data['email']) {
            $user = User::findFirst("email='{$data['email']}'");
            if ($user) {
                $data = array_merge($data, $user->toArray());
                $this->sendForgotPasswordMail($data);
                return ['success' => true, 'message' => 'Reset password mail has been sent successfully.'];
            } else {
                return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No user found with this email.'];
            }
        } elseif (isset($data['username']) && $data['username']) {
            $user = User::findFirst("username='{$data['username']}'");
            if ($user) {
                $data = array_merge($data, $user->toArray());
                $this->sendForgotPasswordMail($data);
                return ['success' => true, 'message' => 'Reset password mail has been sent successfully.'];
            } else {
                return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No user found with this username.'];
            }
        }
    }

    public function sendForgotPasswordMail($data)
    {   
        if (isset($data['reset-link'])) {
           
            $date = new \DateTime('+1 hour');
            $token = array(
                "user_id" => $this->id,
                "email" => $data['email'],
                "exp" => $date->getTimestamp()
            );
            $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->getJwtToken($token, 'HS256', false);
            $link = $data['reset-link'] . base64_encode($token);
            $path = 'core' . DS . 'view' . DS . 'email' . DS . 'userforgotpassword.volt';
            $data['email'] = $data['email'];
            $data['path'] = $path;
            $data['link'] = $link;
            $data['subject'] = 'Password Reset Mail';
            $token = $this->di->getObjectManager()->get('App\Core\Components\SendMail')->send($data);
            return $token;
        } else {
            //todo
         
        }
    }

    // Get user detail from user and user_data table
    public function forgotReset($data)
    {
        if (isset($data['token']) && isset($data['new_password'])) {
            $decoded = $this->di->getObjectManager()->get('App\Core\Components\Helper')
            ->decodeToken(base64_decode($data['token']), false);
            if ($decoded['success'] && $decoded['data']['email']) {
                $user = User::findFirst("email='{$decoded['data']['email']}'");
                if ($user) {
                    $user->save(['password' => $this->getHash($data['new_password'])]);
                    return ['success' => true, 'message' => 'Password has been set successfully.'];
                } else {
                    return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No user found.'];
                }
            } else {
                return ['success' => false, 'code' => 'unknown_error', 'message' => 'Something wrong with token.'];
            }
        }
    }

    // Get user detail from user and user_data table
    public function getDetails()
    {
        if ($this->id) {
            $first = $this->toArray();
            $parts = $this->di->getObjectManager()->get('\App\Core\Models\User\Details')
                            ->getConfig($this->_id);
            if (isset($parts['profile_pic'])) {
                $parts['profile_pic'] = $this->di->getUrl()->get() . $parts['profile_pic'];
            }
            $final = array_merge($first, $parts);
            return ['success' => true, 'message' => '', 'data' => $final];
        } else {
            return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No Customer Found'];
        }
    }

    public static function search($filterParams = [], $fullTextSearchColumns = null)
    {
        $conditions = [];
        if (isset($filterParams['filter'])) {
            foreach ($filterParams['filter'] as $key => $value) {
                $key = trim($key);
                if (array_key_exists(User::IS_EQUAL_TO, $value)) {
                    $conditions[] = "" . $key . " = '" . trim(addslashes($value[User::IS_EQUAL_TO])) . "'";
                } elseif (array_key_exists(User::IS_NOT_EQUAL_TO, $value)) {
                    $conditions[] = "" . $key . " != '" . trim(addslashes($value[User::IS_NOT_EQUAL_TO])) . "'";
                } elseif (array_key_exists(User::IS_CONTAINS, $value)) {
                    $conditions[] = "" . $key . " LIKE '%" . trim(addslashes($value[User::IS_CONTAINS])) . "%'";
                } elseif (array_key_exists(User::IS_NOT_CONTAINS, $value)) {
                    $conditions[] = "" . $key . " NOT LIKE '%" . trim(addslashes($value[User::IS_NOT_CONTAINS])) . "%'";
                } elseif (array_key_exists(User::START_FROM, $value)) {
                    $conditions[] = "" . $key . " LIKE '" . trim(addslashes($value[User::START_FROM])) . "%'";
                } elseif (array_key_exists(User::END_FROM, $value)) {
                    $conditions[] = "" . $key . " LIKE '%" . trim(addslashes($value[User::END_FROM])) . "'";
                } elseif (array_key_exists(User::RANGE, $value)) {
                    if (trim($value[User::RANGE]['from']) && !trim($value[User::RANGE]['to'])) {
                        $conditions[] = "" . $key . " >= '" . $value[User::RANGE]['from'] . "'";
                    } elseif (trim($value[User::RANGE]['to']) && !trim($value[User::RANGE]['from'])) {
                        $conditions[] = "" . $key . " >= '" . $value[User::RANGE]['to'] . "'";
                    } else {
                        $conditions[] = "" . $key . " between '" . $value[User::RANGE]['from'] . "' AND '" . $value[User::RANGE]['to'] . "'";
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

    public function getALL($limit = 1, $activePage = 1, $filters = [])
    {
        $user = $this->di->getRegistry()->getDecodedToken();
        if (count($filters) > 0) {
            $fullTextSearchColumns = "`username`,`email`";
            $query = 'SELECT * FROM \App\Core\Models\User WHERE ';
            $countQuery = 'SELECT COUNT(*) FROM \App\Core\Models\User WHERE ';
            $conditionalQuery = self::search($filters, $fullTextSearchColumns);
            $query .= $conditionalQuery;
            $countQuery .= $conditionalQuery . ' LIMIT ' . $limit . ' OFFSET ' . $activePage ;
            $exeQuery = new Query($query, $this->di);
            $collection = $exeQuery->execute();
            $exeCountQuery = new Query($countQuery, $this->di);
            $collectionCount = $exeCountQuery->execute();
            if ($user['role'] == 'admin') {
                $collectionCount = $collectionCount->toArray();
                $collectionCount[0] = json_decode(json_encode($collectionCount[0]), true);
                return ['success' => true, 'message' => '', 'data' => ['rows' => $collection, 'count' => $collectionCount[0][0]]];
            } else {
                return ['success' => false, 'code' => 'not_allowed', 'message' => 'You are not allowed to view all users'];
            }
        } else {
            if ($user['role'] == 'admin') {
                $users = User::find(['limit' => $limit, 'offset' => $activePage])->toArray();
                $count = User::count();
                return ['success' => true, 'message' => '', 'data' => ['rows' => $users, 'count' => $count]];
            } else {
                return ['success' => false, 'code' => 'not_allowed', 'message' => 'You are not allowed to view all users'];
            }
        }
    }

    public function updateStatus($data)
    {
        if ($data && isset($data['id'])) {
            $user = $this->di->getRegistry()->getDecodedToken();
            if ($user['role'] == 'admin') {
                $users = User::findFirst("id='{$data['id']}'");
                $users->save($data, ['status']);
                return ['success' => true, 'message' => 'Status Updated successfully', 'data' => []];
            } else {
                return ['success' => false, 'code' => 'not_allowed', 'message' => 'You are not allowed to update the status'];
            }
        } else {
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid data.'];
        }
    }

    public function updateConfirmation($data)
    {
        if ($data && isset($data['id'])) {
            $user = $this->di->getRegistry()->getDecodedToken();
            if ($user['role'] == 'admin') {
                $users = User::findFirst("id='{$data['id']}'");
                $users->save(['confirmation' => 1], ['confirmation']);
                return ['success' => true, 'message' => 'Account Confirmed', 'data' => []];
            } else {
                return ['success' => false, 'code' => 'not_allowed', 'message' => 'You are not allowed to confirm '];
            }
        } else {
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid data.'];
        }
    }

    /*
    protected function _postSave($success, $exists)
    {
        try {
            if ($success === true) {
                $keys = $this->getModelsMetaData()->getAttributes($this);
                $keys[] = '_url';
                $data = $this->di->getRequest()->get();
                $user = $this->id;

                $userdb = UserDb::findFirst(["id='{$user}'"]);
                if ($user && !$exists && $this->getMultipleDbManager()->getDefaultDb() != $userdb->db) {
                    $proxyUser = new UserProxy;
                    $proxyUser->save($this->toArray());
                }
                $userData = new User\Data;
                $allowedKeys = $userData->allowedKey;

                foreach ($keys as $key) {
                    if (isset($data[$key])) {
                        unset($data[$key]);
                    }
                }
                $shop = false;
                foreach ($data as $ke => $value) {
                    if (!in_array($ke, $allowedKeys)) {
                        unset($data[$ke]);
                    }
                    if ($ke == 'shops') {
                        $shop = $data['shops'];
                        unset($data['shops']);
                    }
                }
                $userDetails = $this->di->getObjectManager()->create('\App\Core\Models\User\Details');
                $data['user_id'] = $user;

                if ($exists) {
                    if ($this->status == 3) {
                        $this->di->getTokenManager()->disableUserToken($this->getMultipleDbManager()->getDefaultDb(), $user);
                    }
                    $userDetailsCollection = $userDetails->getCollection();
                    $userDetailsCollection->updateOne(['user_id' => $user], ['$set' => $data]);
                } else {
                    $data['_id'] = $userDetails->getCounter('user_details_id');
                    $data['shops'] = [];
                    $userDetails->setData($data);
                    $userDetails->save();
                }

                if ($shop) {
                    foreach ($shop as $K => $value) {
                        if (is_array($value)) {
                            $userDetails->addShop($value, $user);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
        return parent::_postSave($success, $exists);
    }
    */

    public function updateUser($data)
    {
        // todo update_at did not worked as it should work.
        if ($this->id) {
            $this->save($data, ['username']);
            return ['success' => true, 'message' => 'User Details updated successfully.'];
        } else {
            return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No Customer Found'];
        }
    }

    public function adminUpdateUser($data)
    {
        if ($data && isset($data['id'])) {
            $user = User::findFirst("id='{$data['id']}'");
            if ($user) {
                try {
                    $user->save($data, ['username']);
                    return ['success' => true, 'message' => 'User Details updated successfully.'];
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'Something went wrong', 'code' => 'something_wrong'];
                }
            } else {
                return ['success' => false, 'code' => 'no_customer_found', 'message' => 'No Customer Found'];
            }
        } else {
            return ['success' => false, 'code' => 'invalid_data', 'message' => 'Invalid data posted'];
        }
    }

    public function logout()
    {
        if (!$this->di->getSession()->isStarted()) {
            $session = $this->di->getObjectManager()->get('session');
            $session->start();
            $this->di->getSession()->destroy();
        }

        $token = $this->di->getRegistry()->getDecodedToken();
        $success = $this->di->getTokenManager()->removeToken($token);
        $this->di->getSession()->destroy();
        if ($success) {
            return ['success' => true, 'message' => 'Logout Successfully', 'data' => [1]];
        } else {
            return ['success' => false, 'code' => 'logout_failed', 'message' => 'Logout Failed.'];
        }
    }

    public function getConfigByPath($path, $framework = false)
    {
        if ($this->id) {
            return $this->di->getObjectManager()->create('App\Core\Models\User\Details')
                        ->getConfigByKey($path, $this->id);
        } else {
            return [];
        }
    }

    public function setConfig($framework, $path, $value)
    {
        if ($this->id) {
            $this->di->getObjectManager()->create('App\Core\Models\User\Details')
                ->setConfigByKey($path, $value, $this->id);
        } else {
            return [];
        }
    }

    public function getConfig()
    {
        if ($this->id) {
            return $this->di->getObjectManager()->create('App\Core\Models\User\Details')->getConfig($this->id);
        } else {
            return [];
        }
    }

    // upgrade_needed
    public function getUserRoles()
    {
        if ($this->id) {
            return $this->di->getObjectManager()->create('App\Core\Models\User\Details')->getConfig($this->id);
        } else {
            return [];
        }
    }

    public function setTrialPeriod($trialDetails)
    {
        $userId = $trialDetails['user_id'];
        $trialPeriod = $trialDetails['trial_value'];
        $saveStatus = $this->setUsertrialPeriod($userId, $trialPeriod);
        if ($saveStatus) {
            return ['success' => true, 'message' => 'Trial period updated for this user'];
        } else {
            return ['success' => false, 'message' => 'Something went wrong', 'code' => explode('_', $saveStatus->getMessages())];
        }
    }

    public function setUsertrialPeriod($user_id, $trialPeriod)
    {
        $path = 'payment_plan';
        $setTrialPeriod = $this->di->getObjectManager()->get('\App\Core\Models\User\Details')
                                ->setConfigByKey($path, $trialPeriod, $user_id);
        return $setTrialPeriod;
    }

    public function getUsertrialPeriod($user_id)
    {
        $path = 'payment_plan';
        $getTrialPeriod = $this->di->getObjectManager()->get('\App\Core\Models\User\Details')
                                ->getConfigByKey($path, $user_id);
        if ($getTrialPeriod) {
            $this->di->getObjectManager()->get('\App\Shopify\Components\WebhookActions')
                ->logger('Got config trial period => ' . $getTrialPeriod['value'] . ' , with user_id => ' . $user_id, \Phalcon\Logger::CRITICAL, 'feed_create_issue.log');
            return $getTrialPeriod['value'];
        } else {
            $this->di->getObjectManager()->get('\App\Shopify\Components\WebhookActions')
                ->logger('Did not got config trial period. Returned => 7', \Phalcon\Logger::CRITICAL, 'feed_create_issue.log');
            $this->di->getObjectManager()->get('\App\Core\Models\User\Details')->setConfigByKey($path, 7, $user_id);
            return 7;
        }
    }

    public function loginAsUser($proxyUserDetails)
    {
        $proxyLoginId = $proxyUserDetails['id'];
        // $userDetails = User::findFirst(["id='{$proxyLoginId}'"]);
        $userDetails = \App\Core\Models\User::findFirst([['user_id'=>$proxyLoginId]]);
        if ($userDetails) {
            $userToken = $userDetails->getToken();
            return ['success' => true, 'message' => 'User token', 'data' => $userToken];
        } else {
            return ['success' => false, 'message' => 'Invalid user id', 'code' => 'invalid_user_id'];
        }
    }

    public function createWebhook($userDetails)
    {
        $userId = $userDetails['user_id'];
        $shop = $userDetails['shop'];
        $webhookStatus = $this->di->getObjectManager()->get('\App\Connector\Components\Webhook')
                            ->createWebhooks($shop, $userId);
        if ($webhookStatus) {
            return ['success' => true, 'message' => 'Webhook created successfully'];
        } else {
            return ['success' => false, 'message' => 'Invalid API key or access token', 'code' => 'invalid_api_key_or_access_token'];
        }
    }

    public function getExistingWebhooks($userDetails)
    {
        $userId = $userDetails['user_id'];
        $shop = $userDetails['shop'];
        $allWebhooks = $this->di->getObjectManager()->get('\App\Connector\Components\Webhook')
                            ->getExistingWebhooks($shop, $userId);
        if ($allWebhooks) {
            return ['success' => true, 'message' => 'Existing webhooks', 'data' => $allWebhooks[0]];
        }
        return ['success' => false, 'message' => 'Invalid API key or access token', 'code' => 'invalid_api_key_or_access_token'];
    }

    // upgrade_needed
    public function getShop(){
        $decodedToken = $this->di->getObjectManager()->get('App\Core\Components\Helper')
                            ->decodeToken($this->di->getRegistry()->getToken());
        if (isset($decodedToken['data']['shop_id'])) {
            return \App\Shopify\Models\Shop\Details::findFirst(['id="'.$decodedToken['data']['shop_id'].'"']);
        }
        return false;
    }
}
