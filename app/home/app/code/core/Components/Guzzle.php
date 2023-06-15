<?php

namespace App\Core\Components;

use GuzzleHttp\Client;

class Guzzle extends \App\Core\Components\Base
{

    /**
     * @param string $url
     * @param array $headers
     * @param array $data
     * @param string $type
     * @return array/false
     */
    public function call($url, $headers = [], $data = [], $type = 'GET')
    {
        $client = new Client(["verify" => false]);
        switch ($type) {
            case 'DELETE':
                $response = $client->delete($url, ['headers' => $headers, 'query' => $data]);
                break;
            case 'DELETE/FORM':
                $response = $client->delete($url, ['headers' => $headers, 'form_params' => $data]);
                break;

            case 'POST':
                $response = $client->post($url, ['headers' => $headers, 'form_params' => $data]);
                break;

            case 'PUT':
                $response = $client->put($url, ['headers' => $headers, 'json' => $data]);
                break;

            default:
                $response = $client->get($url, ['headers' => $headers, 'query' => $data, 'http_errors' => false]);
                break;
        }

//        if ($this->di->getUser()->id == '608920e62c89040c1c543822') {
//                if (strpos($url, 'getTokenByRefresh') === false){
//                // $res = json_decode($response->getBody()->getContents(), true);
//                print_r($response->getBody()->getContents());die('guzzle die');
//                // var_dump(json_decode($response->getBody()->getContents(), true));
//                }else{
//                    $res = json_decode($response->getBody()->getContents(), true);
//                }
//        }
//        else{
        // print_r($response->getBody()->getContents());

//        if (strpos($url, 'report-fetch') !== false){
//            print '<pre>';
//            print_r($response->getBody()->getContents());
//            print_r($data);
//            die('guzzle die');
//        }

        $bodyContent = $response->getBody()->getContents();

        if(!$this->isJson($bodyContent)) {
            $this->di->getLog()->logContent('Request url : '.$type.' '.$url.PHP_EOL.print_r($data,true).PHP_EOL.'Response data : '.print_r($bodyContent, true), 'info', 'remote_errors.log');
        }

        $res = json_decode($bodyContent, true);

        // $res = json_decode($response->getBody()->getContents(), true);
        
        return $res;
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
