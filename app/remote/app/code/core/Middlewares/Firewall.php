<?php

namespace App\Core\Middlewares;

use Phalcon\Events\Event;

/**
 * FirewallMiddleware
 *
 * Checks the whitelist and allows clients or not
 */
class Firewall implements MiddlewareInterface
{
    /**
     * @var int
     */
    public $timeLimit = 2;

    /**
     * @var int
     */
    public $throttleLimit = 800;
    protected $di;
    /**
     * Before anything happens
     * @param Event $event
     * @param \Phalcon\Mvc\Application $application
     * @return bool
     */
    public function beforeHandleRequest(Event $event, \Phalcon\Mvc\Application $application)
    {

        $start_time = microtime(true);

        $this->di = $application->di;
  
        $isThrottleEnabled = file_exists(BP . '/var/enable-throttle.flag') ? 
            trim(file_get_contents(BP . '/var/enable-throttle.flag')) : 
            0 ;
        if ($isThrottleEnabled ) { 
            $throttle = $this->handleThrottle($application);
            if (!$throttle) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'code' => 'too_much_request', 'message' => 'Limit exeeded.Try after sometime.']);
                exit;
            }
        }
        
        $headers = $this->get_nginx_headers();

        $isDev = isset($_SERVER['CED_IS_DEV']) ? 
            (int)$_SERVER['CED_IS_DEV'] : 
            (file_exists(BP . '/var/is-dev.flag') ? 
                trim(file_get_contents(BP . '/var/is-dev.flag')) : 
                0 
            );
        if ($isDev && $application->request->getMethod() == 'OPTIONS') {
            header("Access-Control-Allow-Headers: " . $headers['Access-Control-Request-Headers'] . "");
            exit;
        }
        
        if (isset($headers['Authorization'])) {
            $bearer = $headers['Authorization'];
        } else if (isset($headers['authorization'])) {
            $bearer = $headers['authorization'];
        } else {
            $bearer = '';
        }

        $authType = '';
        $bearer = preg_replace('/\s+/', ' ', $bearer);
        if (preg_match('/Bearer\s(\S+)/', $bearer, $matches)) {
            $bearer = $matches[1];
            $authType = 'bearer';
        } elseif (preg_match('/Basic\s(\S+)/', $bearer, $matches)) {
            $authType = 'basic';
        } else {
            $session = $application->di->getObjectManager()->get('session');
            $session->start();
        }
        if ($bearer == '' && $application->request->get('bearer')) {
            $bearer = $application->request->get('bearer');
            $session = $application->di->getObjectManager()->get('session');
            $session->start();
        }
        $this->di->getRegistry()->setIsSubUser(false);
        if ($bearer != '' && $authType != 'basic') {
            try {
                $decoded = $application->di->getObjectManager()
                            ->get('App\Core\Components\Helper')->decodeToken($bearer);
                if ($decoded['success']) {
                    $decoded = $decoded['data'];
                    $application->di->getRegistry()->setToken($bearer);


                    $this->validateRequest($application, $decoded);

                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'code' => $decoded['code'], 'message' => $decoded['message']]);
                    exit;
                }
            } catch (\Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
        } else {
            if ($session->has("userId")) {
                $user_id = $session->get("userId");
            } elseif ($application->request->get('user_id')) {
                $user_id = $application->request->get('user_id');
            } else {
                $user = \App\Core\Models\User::findFirst([["username"=>"admin"]]);
                $user_id = (string)$user->getId();
                
            }

            $data = [
                'role'=>'anonymous',
                'user_id'=>$user_id,
            ];
            $this->validateRequest($application, $data);
        }

        return true;
    }

    private function prepareLogData($application)
    {
        $request = $application->di->getRequest();

        $query_param = $request->getQuery();

        $data = [
            '_id'               => $application->di->getRegistry()->getRequestLogId(),
            'user_id'           => $application->di->getUser()->id,
            'ip'                => $request->getClientAddress(),
            'user_agent'        => $request->getUserAgent(),
            'endpoint'          => $query_param['_url']??'',
            'query_params'      => $query_param,
            'request_headers'   => $request->getHeaders(),
            'request_body'      => $request->getRawBody(),
            'method'            => $request->getMethod()
        ];

        return $data;
    }

    /**
     * @param $application
     * @param $decodedToken
     * @return bool
     */
    public function validateRequest($application, $decodedToken)
    {
        if (isset($decodedToken['aud']) && $decodedToken['aud'] != $_SERVER['REMOTE_ADDR'] ) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'code' => 'wrong_aud', 'message' => 'Access forbidden']);
            exit;
        }
        $application->di->getRegistry()->setDecodedToken($decodedToken);

        $user = $application->di->getObjectManager()->create('\App\Core\Models\User')->findFirst([
            [
                '_id' => new \MongoDB\BSON\ObjectId($decodedToken['user_id'])
            ]
        ]);
        
        $application->di->getRegistry()->setRequestingApp('angular');

        $app_code = [
            'shopify' => "default"
        ];
        $app_tag = "default";

        if ( $application->di->getRequest()->getHeader('appCode') != null ) {
            $app_code = json_decode(base64_decode($application->di->getRequest()->getHeader('appCode')), true);
        } else if ( isset($application->di->getRequest()->get()['app_code']) ) {
            $app_code = $application->di->getRequest()->get()['app_code'];
        } else if (isset($application->di->getRequest()->getJsonRawBody(true)['app_code'])){
            $app_code = $application->di->getRequest()->getJsonRawBody(true)['app_code'];
        }

        if ( $application->di->getRequest()->getHeader('appTag') != null ) {
            $app_tag = $application->di->getRequest()->getHeader('appTag');
        }

        if ($user) {
            $user->id = (string)$user->_id;
            $application->di->setUser($user);
            $application->di->getAppCode()->set($app_code);
            $application->di->getAppCode()->setAppTag($app_tag);
            $acl = unserialize(
                file_get_contents(BP . DS . 'app' . DS . 'etc' . DS . 'security' . DS . 'acl.data')
            );
            $module = $application->router->getModuleName();
            $action = $application->router->getActionName();
            $controller = $application->router->getControllerName();

           
            if ($acl->isAllowed($decodedToken['role'], $module . '_' . $controller, $action)) {
                $application->di->getUser()->setSubUserId( $decodedToken['child_id'] ?? 0 );
                return $this->checkChildAcl($decodedToken, $module . '_' . $controller, $action);
            } else {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 'access_forbidden', 'message' => $this->di->getLocale()->_('access forbidden',['msg'=>$decodedToken['role'].' '.$module . '_' . $controller.'_'.$action])]);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'code' => 'token_user_not_found', 'message' => 'Access forbidden']);
            exit;
        }
    }

    function get_nginx_headers($function_name = 'getallheaders')
    {
        $all_headers = array();
        if (function_exists($function_name)) {
            $all_headers = $function_name();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5)=='HTTP_') {
                    $name = substr($name, 5);
                    $name = str_replace('_', ' ', $name);
                    $name = strtolower($name);
                    $name = ucwords($name);
                    $name = str_replace(' ', '-', $name);

                    $all_headers[$name] = $value;
                } elseif ($function_name == 'apache_request_headers') {
                    $all_headers[$name] = $value;
                }
            }
        }
        $this->di->getRegistry()->setHeaders($all_headers);
        return $all_headers;
    }

    public function handleThrottle($application)
    {
        $current_timestamp = time();
        $client_ip = $application->request->getClientAddress();
        $ip = $application->di->getCache()->get($client_ip);
        if ($ip) {
            $data = explode('_', $ip);
            $old_timestamp = $data[0];
            $old_count = $data[1];
            if ($old_timestamp < $current_timestamp - $this->timeLimit) {
                $application->di->getCache()->set($client_ip, $current_timestamp . '_' . '1');
                return true;
            } elseif ($old_count < $this->throttleLimit) {
                $old_count++;
                $application->di->getCache()->set($client_ip, $old_timestamp . '_' . $old_count);
                return true;
            } else {
                $old_count++;
                $application->di->getCache()->set($client_ip, $old_timestamp . '_' . $old_count);
                return false;
            }
        } else {
            $application->di->getCache()->set($client_ip, $current_timestamp . '_' . '1');
            return true;
        }
    }

    public function checkChildAcl($decoded, $path, $action)
    {
        if (isset($decoded['child_id']) && $decoded['child_id']) {
            $mongo = new \App\Core\Models\BaseMongo;
            $collection = $mongo->getCollectionForTable("sub_user");
            $subuser = $collection->findOne(['_id' => $decoded['child_id']]);
            $this->di->getRegistry()->setIsSubUser(true);
            $this->di->setSubUser($subuser);
            if ($subuser['_id']) {
                if($subuser['resources'] == 'all')
                    return true;
                $child_acl = unserialize(
                    file_get_contents(BP . DS . 'app' . DS . 'etc' . DS . 'security' . DS .'child'.DS. 'acl_'.$subuser['_id'].'.data')
                );
                if ($child_acl->isAllowed('child_' . $decoded['child_id'], $path, $action)) {
                    return true;
                } else {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'code' => 'child_access_forbidden', 'message' => 'Access forbidden']);
                    exit;
                }
            } else {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['success' => false, 'code' => 'child_token_user_not_found', 'message' => 'Access forbidden']);
                exit;
            }
        } else {
            return true;
        }
    }

    public function isRequestOpen($application)
    {
        $module = $application->router->getModuleName();
        $action = $application->router->getActionName();
        $controller = $application->router->getControllerName();
        return true;
    }

    /**
     * Calls the middleware
     *
     * @param Micro $application
     *
     * @returns bool
     */
    public function call(\Phalcon\Mvc\Application $application)
    {
        return true;
    }
}
