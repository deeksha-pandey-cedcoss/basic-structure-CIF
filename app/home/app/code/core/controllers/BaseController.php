<?php
namespace App\Core\Controllers;

use Phalcon\Mvc\Controller;

class BaseController extends Controller
{
    public function prepareResponse($data, $type = 'json')
    {
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        $app = $this->di->getRegistry()->getRequestingApp();
        try {
            if ($type == 'json') {
                $controller = ucfirst(str_replace('-', '', ucwords($this->router->getControllerName(), '-')));
                $action = $this->router->getActionName();

                $modified_response = $this->di->get('App\\'.ucfirst($app).'\Components\Modifiers\\'.$controller)->$action($data);

                $response = $this->response->setJsonContent($modified_response);
            }
        } catch (\Exception $exception) {
            $response = $this->response->setJsonContent($data);
        }

        if(!isset($modified_response)) {
            $modified_response = $data;
        }
        $this->logResponse($response, $modified_response);
        // sleep(2);
        return $response;
    }

    private function logResponse($responseObj, $modifiedResponse)
    {
        $configData = $this->di->getRegistry()->getAppConfig();
        $log_data = [
            '_id'               => $this->di->getRegistry()->getRequestLogId(),
            'channel'           => $configData['marketplace'],
            'response_headers'  => $responseObj->getHeaders()->toArray()
        ];

        $request_logger = $this->di->getObjectManager()->get('\App\Core\Components\RequestLogger');

        if($request_logger->canLogResponse($modifiedResponse)) {
            $log_data['response_body'] = $modifiedResponse;
        }

        $request_logger->logContent($log_data);
    }
}
