<?php

namespace App\Connector\Components;

class ApiClient extends \App\Core\Components\Base
{
    public $_refreshToken = '';

    public $_token;

    public $_errorMsg = '';

    public $_tokenType = '';

    public $_appCode = 'shopify';

    public function init($tokenType, $getFromCache = true, $appCode = false)
    {
        if (!$appCode && isset($this->di->getAppCode()->get()[$tokenType])) {
            $appCode = $this->di->getAppCode()->get()[$tokenType];
        } elseif (!$appCode) {
            $appCode = "default";
        }
        $generateNew = $getFromCache === false ? true : false;
        $this->_tokenType = $tokenType;
        $this->_appCode = $appCode;
        $this->getTokenFromRefresh($generateNew);
        return $this;
    }

    /**
     * call the internal api with this function
     * @param string $endpoint
     * @param array $headers
     * @param array $data
     * @param string $type
     * @return array/false
     */
    public function call($endpoint, $headers = [], $data = [], $type = 'GET')
    {
        if (!empty($this->_errorMsg)) {
            return [
                'success' => false,
                'message' => $this->_errorMsg
            ];
        }
        if (!$token = $this->_token) {
            return ['success' => false, 'message' => isset($this->_errorMsg) ?? "Error in fetching token" ];
        }

        /* $helper = $this->di->getObjectManager()->get('App\Core\Components\Helper'); */
        $base_uri = $this->di->getConfig()->get('apiconnector')->get('base_url');
        /* if (!$token = $this->getTokenFromRefresh()) {
            return ['success' => false, 'message' => 'error in refershing token'];
        } */

        $headers['Authorization'] = $token;
        // print($headers);
        // die;
        /*$headers['Authorization'] ="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJ1c2VyX2lkIjoiMiIsInJvbGUiOiJjdXN0b21lcl9hcGkiLCJleHAiOjE1NTY4OTAzMzksImF1ZCI6IjE5Mi4xNjguMC40OSIsImlhdCI6MTU1NjgwMzkzOSwiaXNzIjoiaHR0cHM6XC9cL2FwcHMuY2VkY29tbWVyY2UuY29tIiwibmJmIjoxNTU2ODAzOTM5LCJ0b2tlbl9pZCI6MTU1NjgwMzkzOX0.fo8PSihrZ6DsPt_IhLRzTyABQHK_Wf1zHbiwClavAdgX5VSTUfGjuG-JBT4IkUeLhSF5arlAtNG5iYoDvphklKCpbFrOB70SuKxUekh9qJ9T1qH2YefgQIUHcL8fzCCk21DSyG_UjUF7SIXvUyJRXRoTMVDS3_Q2FuOcoHUzBNFlXTnRRikyqO-EqXCz64RfjgiktJnjeGwvq4LhYzQovVCvjOblMEPi6cmskLwcmg2XnZHExS0YVqIlgX8Cc3AbUfherxhyiwHfHwzWUXcEBaWUhzJdV4v52lQb-FNpP0MYrGE9kg3zD4nutPoKmdFLJFwwj_UPZBpsEbTKSPxB4w";*/
        

        $url = $base_uri.'webapi/rest/v1/'.$endpoint;
        // print_r($url);
        // print_r($headers);
        // print_r($data);
        // print_r($type);
        // die;
        
        $response = $this->di->getObjectManager()->get('App\Core\Components\Guzzle')
                        ->call($url, $headers, $data, $type);

        if ($response['success'] == false && (isset($response['code'])) && ($response['code'] == 'token_expired')) {
            if (!$token = $this->getTokenFromRefresh(true)) {
                return ['success' => false, 'message' => 'error in refershing token'];
            }
            $headers['Authorization'] = $token;
            $response = $this->di->getObjectManager()->get('App\Core\Components\Guzzle')
                            ->call($url, $headers, $data, $type);
        }
        return $response;
    }

    /**
     * Get a token from refresh token.
     * @param boolean $genrateNew pass ture if old token was invalid.
     * @return string/boolean $token
     */
    public function getTokenFromRefresh($genrateNew = false)
    {
        if ((!$this->_token = $this->di->getCache()->get('api_token_'.$this->_tokenType. '_' . $this->_appCode)) || $genrateNew) {
            $base_uri = $this->di->getConfig()->get('apiconnector')->get('base_url');
            $refreshToken = $this->di->getConfig()->get('apiconnector')
            ->get($this->_tokenType)
            ->get($this->_appCode)
            ->get('refresh_token');

            $tokenData = $this->di->getObjectManager()->get('App\Core\Components\Guzzle')
                    ->call($base_uri.'core/token/getTokenByRefresh', ['Authorization' => $refreshToken]);
            if (isset($tokenData['success']) && isset($tokenData['data']['token'])) {
                $this->_token = $tokenData['data']['token'];
                $this->di->getcache()->set('api_token_'.$this->_tokenType . '_' . $this->_appCode, $this->_token);
                return $this;
            } else {
                $this->_errorMsg = isset($tokenData['message']) ? $tokenData['message'] : "Error Obtaining token from Remote Server. Kindly contact Remote Host for more details.";
                return $this;
            }
        } else {
            $this->_token = $this->di->getCache()->get('api_token_'.$this->_tokenType. '_' . $this->_appCode);
            return $this;
        }
    }
}
