<?php

namespace App\Connector\Controllers;

class RequestController extends \App\Core\Controllers\BaseController
{

    const GENERAL_ERROR_MSG = "We didn't anticipated you landing here ! Please reinstall the app again or send us your experience. We will help you guide through.";

    const GENERAL_MSG = "Sorry..<br /> It's not you.<br /> It's us.<br /> Mind sending a error report to us.";

    public $_shopId;

    public $_postData;

    public $_postDataFromRemote;

    public $_errorMsg = false;

    public $_reinstallFlag = false;

    public $_frontend_app_url = 'http://localhost:4000/';

    public $_ignore_global_frontend_url = false;

    public function initialize()
    {

        $helper = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
        $this->_postDataFromRemote = $postData = $this->request->get();
        $this->di->getLog()->logContent(' POST DATA FROM REMOTE = ' . print_r($postData, true), 'critical', 'commence_home_auth.log');

        // This code is not executed from now
        /*if (isset($postData['state']) && !empty($postData['state'])) {
            $user = \App\Core\Models\User::findFirst([['_id' => $postData['state']]]);
            if ($user) {
                $user->id = (string)$user->_id;
                $this->di->setUser($user);
                $decodedToken = [
                    'role' => 'admin',
                    'user_id' => $postData['state'],
                ];
                $this->di->getRegistry()->setDecodedToken($decodedToken);
            }
        }*/

        if (!isset($postData['data'])) {
            if (isset($postData['message'])) {
                $this->_reinstallFlag = true;
                $this->_errorMsg = $postData['message'];
                // Is it confirm that always state is a user_id
                /*if ($this->getShopifyURL($postData['state']) != '') {
                    $this->_errorMsg .= '&shop=' . $this->getShopifyURL($postData['state']);
                }*/
            } else {
                $this->_reinstallFlag = true;
                $this->_errorMsg = "We didn't anticipated you landing here ! Please reinstall the app again or send us your experience. We will help you guide through.";
            }
        } else {
            $configData = $this->di->getConfig()->toArray();
            if (isset($postData['app_code'])) {
                $public_key = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['public_key'];
                $this->_frontend_app_url = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['frontend_app_url']  ?? $configData['frontend_app_url'];
                $this->_ignore_global_frontend_url = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['ignore_global_frontend_url']  ?? $configData['ignore_global_frontend_url'] ?? '';
            } else {
                $postData['app_code'] = 'default';
                $public_key = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['public_key'];
                $this->_frontend_app_url = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['frontend_app_url'] ?? $configData['frontend_app_url'];
                $this->_ignore_global_frontend_url = $configData['apiconnector'][$postData['marketplace']][$postData['app_code']]['ignore_global_frontend_url'] ?? $configData['ignore_global_frontend_url'];
            }
            $this->di->getAppCode()->set([
                $postData['marketplace'] => $postData['app_code']
            ]);

            if (!isset($public_key)) {
                $this->_errorMsg = "Unauthorized Access !!. Help us improve your experience by sending an error report.";
            } else {
                $this->_postData = json_encode($helper->decodeToken($postData['data'], false, base64_decode($public_key)));
                $this->_postData = json_decode($this->_postData, true);
                if (!isset($this->_postData['data']['data']['shop_id']) || ($this->_postData['data']['data']['shop_id'] == 0) || empty($this->_postData['data']['data']['shop_id'])) {
                    $msg = isset($this->_postData['message']) ? ($this->_postData['message']) : '';
                    $this->_errorMsg = $msg . ". Invalid/No Shop Found. Kindly contact Remote Host to provide valid shop id.";
                } else {
                    $this->_shopId = $this->request->get('shop_id');
                }
            }
        }
    }

    public function commenceHomeAuthAction()
    {
        if ($this->_errorMsg) {

            return $this->response->redirect($this->_frontend_app_url . 'show/message?success=false&message=' . $this->_errorMsg);
        }
        if (isset($this->_postData['success']) && $this->_postData['success']) {
            if ($model = $this->di->getConfig()->connectors
                ->get($this->_postDataFromRemote['marketplace'])->get('source_model')
            ) {

                $this->_postData['rawResponse'] = $this->_postDataFromRemote;
                $homeAppResponse = $this->di
                    ->getObjectManager()
                    ->get('App\Connector\Models\SourceModel')->commenceHomeAuth($this->_postData);

                $this->di->getLog()->logContent('response =' . json_encode($homeAppResponse), 'critical', 'commence_home_auth.log');
                // if success , user created successfully else already exist or error
                if ($homeAppResponse['success']) {
                    // case if user already exist , redirect to dashboard else error
                    if (isset($homeAppResponse['redirect_to_dashboard'])) {
                        $token = $this->di->getUser()->getToken();
                        if ($state = $this->session->get("requested_current_route")) {
                            if ($this->_ignore_global_frontend_url) {
                                return $this->response->redirect($this->_frontend_app_url . '?user_token=' . $token . '&shop=' . $homeAppResponse['shop']);
                            }
                            return $this->response->redirect($this->_frontend_app_url . $state = $this->session->get("requested_current_route") . '?user_token=' . $token . '&shop=' . $homeAppResponse['shop']);
                        }
                        if ($this->_ignore_global_frontend_url) {
                            return $this->response->redirect($this->_frontend_app_url . '?user_token=' . $token . '&shop=' . $homeAppResponse['shop']);
                        }
                        return $this->response->redirect($this->_frontend_app_url . 'auth/login?user_token=' . $token . '&shop=' . $homeAppResponse['shop'] . '&connectionStatus=' . $homeAppResponse['connectionStatus']);
                        // redirect to frontend with dashboard
                    } else {
                        unset($homeAppResponse['success']);
                        if ($this->_ignore_global_frontend_url) {
                            $token = $this->di->getUser()->getToken();
                            return $this->response->redirect($this->_frontend_app_url . '?user_token=' . $token . '&code=shopify_installed&success=true&' . http_build_query($homeAppResponse));
                        }
                        return $this->response->redirect($this->_frontend_app_url . 'show/message?success=true&' . http_build_query($homeAppResponse));
                    }
                } else {
                    return $this->response->redirect($this->_frontend_app_url . 'show/message?success=false&message1=' . $homeAppResponse['message'] ?? 'Something Went Wrong From Our Side');
                }
            } else {
                return $this->response->redirect($this->_frontend_app_url . 'show/message?success=false&message2=' . RequestController::GENERAL_MSG);
            }
        } else {
            $this->di->getLog()->logContent('response =' . json_encode($this->_postData), 'critical', 'commence_error_auth.log');
            return $this->response->redirect($this->_frontend_app_url . 'show/message?success=false&message3=' . RequestController::GENERAL_ERROR_MSG);
        }
    }


    public function shopifyCurrentRouteAction()
    {
        $rawData = $this->di->getRequest()->get();

        if (isset($rawData['current_route'])) {
            $this->session->set("requested_current_route", $rawData['current_route']);
        } else {
            $this->session->set("requested_current_route", false);
        }
        unset($rawData['_url']);
        unset($rawData['current_route']);
        return $this->response->redirect($this->di->getConfig()->get('backend_base_url') . "apiconnect/request/auth?" . http_build_query($rawData));
    }

    private function getShopifyURL($user_id)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable("user_details");
        $user_data = $collection->findOne(['user_id' => $user_id]);
        $shop_url = '';
        foreach ($user_data['shops'] as $value) {
            if (isset($value['domain']) && $value['marketplace'] == 'shopify') {
                $shop_url = $value['domain'];
            }
        }
        return $shop_url;
    }

    public function disconnectAccountAction($user_id = false)
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        $helper = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');
        return $this->prepareResponse($helper->disconnectAccount($rawBody));
    }

    public function getConnectedAccountAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $helper = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');
        return $this->prepareResponse($helper->getAllConnectedAcccounts(false, $rawBody));
    }
}
