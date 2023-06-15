<?php
namespace App\Core\Components;

class RequestLogger extends Base
{
    public function logContent($data)
    {
        // $this->router->getModuleName() === 'webapi' && $this->router->getControllerName() === 'rest' && $this->router->getActionName() === 'v1'
        $query_param = $this->di->getRequest()->getQuery();
        if(isset($query_param['_url']) && (strpos($query_param['_url'], 'webapi/rest/v1') !== false) && $this->di->getConfig()->get('request_log_enabled'))
        {
            return $this->di->getObjectManager()->get('\App\Core\Models\RequestLog')->insert($data);
        }
        else
        {
            return false;
        }
    }

    public function canLogResponse($response)
    {
        if(isset($response['success']) && $response['success'] === false) {
            return true;
        }
        else {
            $query_param = $this->di->getRequest()->getQuery();

            // "_url" => "/webapi/rest/v1/bulk/operation/productcount"

            if(isset($query_param['_url'])) {
                $allowed_endpoints = [];
                if($endpoint_str = $this->di->getConfig()->get('response_log_endpoints')) {
                    $allowed_endpoints = explode(',', $endpoint_str);
                }

                if(in_array(str_replace('/webapi/rest/v1/', '', $query_param['_url']), $allowed_endpoints)) {
                    return true;
                }
            }
        }

        return false;
    }

    /*private function prepareLogData()
    {
        $request = $this->di->getRequest();

        // var_dump($request->getMethod());
        // var_dump($request->getQuery());
        // var_dump($request->getPost());
        // var_dump($request->getPut());
        // var_dump($request->getServer());
        // die;

        $query_param = $request->getQuery();

        $data = [
            '_id'               => $this->di->getRegistry()->getRequestLogId(),
            // 'user_id'           => $this->di->getUser()->id,
            'channel'           => 'shopify',
            'ip'                => $request->getClientAddress(),
            'user_agent'        => $request->getUserAgent(),
            'endpoint'          => $query_param['_url']??'',
            'query_params'      => $query_param,
            'request_headers'   => $request->getHeaders(),
            'request_body'      => $request->getRawBody(),
            'method'            => $request->getMethod()
            'http_status'       => 200,
            // 'response_body'     => []
        ];

        return $data;
    }*/
}