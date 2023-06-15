<?php

/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @category    Ced
 * @package     Ced_Shoifyhome
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CEDCOMMERCE (http://cedcommerce.com/)
 * @license     http://cedcommerce.com/license-agreement.txt
 */

namespace App\Connector\Models;

use App\Core\Models\SourceModel as BaseSourceModel;

class SourceModel extends BaseSourceModel implements SourceModelInterface
{
    const USER_DETAILS_CONTAINER = 'user_details';
    const PRODUCT_CONTAINER = 'product_container';
    const QUEUED_TASKS = 'queued_tasks';
    const REPORT_CONTAINER = 'report_container';
    // Onboarding Code Starts
    public function setupHomeUser($data)
    {

        $this->di->getLog()->logContent('setupHomeUser FUN || post data = ' . json_encode($data), 'info', 'commence_home_auth.log');

        $data['data']['data']['app_code'] = $data['rawResponse']['app_code'];
        $remoteShopId = (string) $data['data']['data']['shop_id'];

        if (!empty($data['rawResponse']['state'])) {
            $state = json_decode($data['rawResponse']['state'], true);
        }

        $user = $this->di->getObjectManager()->get('App\Core\Models\User\Details')->getUserByRemoteShopId($remoteShopId, ['user_id' => 1, 'shops' => 1], $data['data']['data']['app_code']);

        $this->di->getLog()->logContent(' setupHomeUser FUN || validate user present or not for same app =' . json_encode($user), 'info', 'commence_home_auth.log');

        $shop = false;
        if (isset($user['shops'])) {
            foreach ($user['shops'] as $sh) {
                if ($sh['remote_shop_id'] == $remoteShopId) {
                    $this->di->getLog()->logContent('  setupHomeUser FUN || SHOP  PRESENT =' . json_encode($shop), 'info', 'commence_home_auth.log');
                    $shop = $sh;
                    break;
                }
            }
        }

        if ($user && $shop) {
            $getUser = \App\Core\Models\User::findFirst([['_id' => $user['user_id']]]);
            $getUser->id = (string) $getUser->_id;
            $this->di->setUser($getUser);
            $this->di->getLog()->logContent(' setupHomeUser FUN || user , shop , and app present =' . json_encode($shop), 'info', 'commence_home_auth.log');
            return [
                'success' => true,
                "user_was_present" => 1,
                "shop_was_present" => 1,
                "app_was_present" => 1,
                'shop' => $shop,
            ];
        } else {
            $marketplace = $data['data']['data']['marketplace'];
            $appCode = $data['data']['data']['app_code'];

            $user = $this->di->getObjectManager()->get('App\Core\Models\User\Details')->getUserByRemoteShopId((string) $data['data']['data']['shop_id'], ['user_id' => 1, 'shops' => 1]);

            $this->di->getLog()->logContent(' setupHomeUser FUN || validate user present or not =' . json_encode($user), 'info', 'commence_home_auth.log');

            if ($user) {
                $getUser = \App\Core\Models\User::findFirst([['_id' => $user['user_id']]]);
                $getUser->id = (string) $getUser->_id;
                $this->di->setUser($getUser);

                $app = [
                    "code" => $appCode,
                ];

                if (isset($user['shops'])) {
                    foreach ($user['shops'] as $sh) {
                        if ($sh['remote_shop_id'] == $remoteShopId) {
                            $shop = $sh;
                            break;
                        }
                    }
                }

                $this->di
                    ->getObjectManager()
                    ->get('App\Connector\Models\User\Shop')
                    ->addApp($remoteShopId, $app, $user['user_id']);

                $this->di->getLog()->logContent(' setupHomeUser FUN || user and shop present ,but app was not present =' . json_encode($app), 'info', 'commence_home_auth.log');

                return [
                    'success' => true,
                    "user_was_present" => 1,
                    "shop_was_present" => 1,
                    "app_was_present" => 0,
                    'shop' => $shop,
                ];
            } else {

                $shopResponse = $this->di->getObjectManager()->get('\App\Connector\Components\ApiClient')->init($marketplace, true, $appCode)->call('/shop', [], [
                    'shop_id' => $data['data']['data']['shop_id'],
                    'app_code' => $appCode,
                ]);

                $this->di->getLog()->logContent('user details. shop_id=' . $data['data']['data']['shop_id'] . ' shop response = ' . json_encode($shopResponse), 'info', 'onyx' . DS . 'User' . DS . date("Y-m-d") . DS . 'User.log');

                if (!$shopResponse['success'] || is_null($shopResponse) || empty($shopResponse)) {
                    return [
                        'success' => false,
                        'message' => isset($shopResponse['errors']) ? $shopResponse['errors'] : 'Error fetching data from ' . $marketplace . ', Please try again later or contact our next available support member.',
                    ];
                }

                $filter = !empty($state['user_id']) ? ['user_id' => $state['user_id']] : ['email' => $shopResponse['data']['email']];

                $user = \App\Core\Models\User::findFirst([$filter]);

                $this->di->getLog()->logContent('user details || user present or not = ' . json_encode($user), 'info', 'onyx' . DS . 'User' . DS . date("Y-m-d") . DS . 'User.log');

                if (empty($user)) {
                    $shopData['username'] = $shopResponse['data']['username'] ?? $shopResponse['data']['name'];
                    $shopData['email'] = $shopResponse['data']['email'];
                    $shopData['password'] = $this->di->getConfig()->security->default_shopify_sign;
                    $userModel = $this->di->getObjectManager()->create('\App\Core\Models\User');
                    $userModel->createUser($shopData, 'customer', true);

                    $userModel->id = (string) $userModel->_id;
                    $this->di->setUser($userModel);
                    $this->di->getLog()->logContent(' user created successfully ' . json_encode($userModel->id), 'info', 'onyx' . DS . 'User' . DS . date("Y-m-d") . DS . 'User.log');
                    $responseData = [
                        'success' => true,
                        "user_was_present" => 0,
                        "shop_was_present" => 0,
                        "app_was_present" => 0,
                    ];
                } else {
                    $user = \App\Core\Models\User::findFirst([['_id' => $user->_id]]);
                    $user->id = (string) $user->_id;
                    $this->di->setUser($user);
                    $responseData = [
                        'success' => true,
                        "user_was_present" => 1,
                        "shop_was_present" => 0,
                        "app_was_present" => 0,
                    ];
                }
                $shopData = $shopResponse['data'];
                $shopData['apps'] = [["code" => $appCode]];
                $shopData['remote_shop_id'] = $remoteShopId;
                $shopData['marketplace'] = $marketplace;

                $shopCollection = $this->di->getObjectManager()->create('\App\Core\Models\User\Details');
                $sourceModel = $this->di->getObjectManager()->get($this->di->getConfig()->connectors
                        ->get($marketplace)->get('source_model'));

                if (method_exists($sourceModel, 'prepareShopData')) {
                    $sourceModel->prepareShopData($data, $shopData);
                }
                if (method_exists($sourceModel, 'addWarehouseToShop')) {
                    $sourceModel->addWarehouseToShop($data, $shopData);
                }
                $responseData['shop'] = $shopData;
                $shopRes = $shopCollection->addShop($shopData, false, ["remote_shop_id"]);

                if (isset($state['source'],$state['source_shop_id'],$state['user_id'])) {
                    $source_shop_id = $state['source_shop_id'];
                    $target_shop_id = $shopRes['data']['shop_id'];

                    $sourceData  = [
                        'shop_id' => $source_shop_id,
                        'source'=>$state['source'],
                    ];
                    $targetData = [
                        'shop_id' => $target_shop_id,
                        'target' => $marketplace
                    ];

                    $this->addSourcesAndTargets([],$sourceData,$target_shop_id,$state['user_id']);
                    $this->addSourcesAndTargets([],$targetData,$source_shop_id,$state['user_id']);
                }

                return $responseData;
            }
        }
    }

    public function addSourcesAndTargets($shop , $data ,$shop_id,$userId = false){

        if(!$userId){
            $userId = $this->di->getUser()->id;
        }

        $fieldname = isset($data['source']) ? "sources" : "targets";

        $sourceTargets = [
            'shop_id' => $data['shop_id'],
            'code' => $data['source'] ?? $data['target']
        ];

        $formattedSourceTargets = isset($shop[$fieldname]) ? array_merge($shop[$fieldname],[$sourceTargets]) : [$sourceTargets];

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $user = $mongo->getCollection('user_details');

        return $user->updateOne(
            [
                "_id"=>new \MongoDB\BSON\ObjectId($userId),
            ],
            [
                '$set'=>[
                    'shops.$[shop].'.$fieldname => $formattedSourceTargets
                ]
            ],
            [
                'arrayFilters' => [
                    [
                        'shop._id' => $shop_id,
                    ]
                ]
            ]

        );

    }

    public function commenceHomeAuth($remotePostData)
    {
        $this->di->getLog()->logContent(' INSIDE commenceHomeAuth FUNCTION = ', 'critical', 'commence_home_auth.log');
        $response = $this->setupHomeUser($remotePostData);
        $remotePostData['data']['data']['app_code'] = $remotePostData['rawResponse']['app_code'];

        $marketplace = $remotePostData['data']['data']['marketplace'];
        $appCode = $remotePostData['data']['data']['app_code'];

        $result = ["success" => 0];
        if ($response['success']) {
            $result['success'] = 1;
            $result['heading'] = 'Congratulations!!';
            $result['message'] = 'Welcome onboard. Your ' . $marketplace . ' store has been succesfully set up.';

            if (isset($response['shop_was_present']) && $response['shop_was_present']) {

                if (isset($response['shop']['email'])) {
                    //$this->di->getObjectManager()->get("\App\Shopifyhome\Components\Mail\Seller")->onboard($response['shop']['email']);
                }
            }
            if (!$response['app_was_present']) {

                try {
                    if (!empty($this->di->getConfig()->webhook->get($marketplace))) {
                        $this->registerUninstallWebhook($response['shop'], $appCode);
                    }
                } catch (\Exception $e) {
                    $this->di->getLog()->logContent('error registering uninstall webhook for user id : ' . $this->di->getUser()->id, 'info', 'uninstallWebhook.log');
                }
            }
            $result['redirect_to_dashboard'] = 1;
            $result['shop'] = $marketplace;
            $result['connectionStatus'] = 1;
        }
        return $result;
    }
    // Onboarding Code Ends

    // Register Webhook code starts
    public function routeRegisterWebhooks($shop, $marketplace, $appCode)
    {
        $userId = $this->di->getUser()->id;
        $handlerData = [
            'type' => 'full_class',
            'class_name' => '\App\Connector\Models\SourceModel',
            'method' => 'registerWebhooks',
            'handle_added' => 1,
            'queue_name' => $appCode . '_register_webhook',
            'user_id' => $userId,
            'data' => [
                'user_id' => $userId,
                'remote_shop_id' => $shop['remote_shop_id'],
                'marketplace' => $marketplace,
                'appCode' => $appCode,
                'shop' => $shop,
                'cursor' => 0,
            ],
        ];

        return [
            'success' => true,
            'message' => 'Registering Webhooks ...',
            'queue_sr_no' => $this->di->getMessageManager()->pushMessage($handlerData),
        ];
    }

    public function registerWebhooks($sqsData)
    {
        $userId = $this->di->getUser()->id;
        $shop = $sqsData['data']['shop'];
        $appCode = $sqsData['data']['appCode'];
        $marketplace = $sqsData['data']['marketplace'];
        $remote_shop_id = $sqsData['data']['remote_shop_id'];

        $webhookHanldingApps = [];
        $registeredWebhooks = [];

        foreach ($shop['apps'] as $app) {
            if (isset($app['webhooks'])) {
                $webhookHanldingApps[$app['code']] = $app;

                foreach ($app['webhooks'] as $webhook) {
                    $registeredWebhooks[$webhook['code']] = 1;
                }
            }
        }

        if (!$this->di->getCache()->get('marketplaceWebhooks_' . $userId . '_' . $appCode)) {
            $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                ->init($marketplace, false, $appCode)
                ->call('/marketplaceswebhooks', [], ['shop_id' => $remote_shop_id], 'GET');
            if (isset($remoteResponse['success'])) {
                $webhooks = $remoteResponse['Webhooks'][$marketplace];
                $marketplaceswebhooks = [];
                if (!empty($webhooks)) {
                    foreach ($webhooks as $ky => $webhook) {
                        $webhook['marketplace'] = $marketplace;
                        $webhook['app_code'] = $appCode;
                        $webhook['action'] = $webhook['code'];
                        $webhook['queue_name'] = $appCode . '_' . $webhook['code'];
                        $marketplaceswebhooks[$webhook['code']] = $webhook;
                    }
                }
                $this->di->getCache()->set('marketplaceWebhooks_' . $userId . '_' . $appCode, $marketplaceswebhooks);
            } else {
                return true;
            }
        }

        if (!$this->di->getCache()->get('appsWebhooks_' . $userId . '_' . $appCode)) {
            $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                ->init($marketplace, false, $appCode)
                ->call('/appsWebhooks', [], ['shop_id' => $remote_shop_id], 'GET');
            print_r($remoteResponse);
            print_r("app webhook");
            if (isset($remoteResponse['success'])) {
                $webhooks = $remoteResponse['data'];
                $appCodeWebhooks = [];
                if (!empty($webhooks)) {
                    foreach ($webhooks as $ky => $webhook) {
                        $webhook['marketplace'] = $marketplace;
                        $webhook['app_code'] = $appCode;
                        $webhook['action'] = $webhook['code'];
                        $webhook['queue_name'] = $appCode . '_' . $webhook['code'];
                        $appCodeWebhooks[$webhook['code']] = $webhook;
                    }
                }
                $this->di->getCache()->set('appsWebhooks_' . $userId . '_' . $appCode, $appCodeWebhooks);
            } else {
                return true;
            }
        } else {
            $appCodeWebhooks = $this->di->getCache()->get('appsWebhooks_' . $userId . '_' . $appCode);
        }
        print_r($registeredWebhooks);
        print_r("Register");

        if (count($registeredWebhooks)) {
            if (isset($webhookHanldingApps[$appCode])) {
                foreach ($appCodeWebhooks as $code => $savedWebhook) {
                    if (isset($registeredWebhooks[$code])) {
                        unset($appCodeWebhooks[$code]);
                    }

                }
                if (count($appCodeWebhooks)) {
                    $this->callRegisterWebhook($appCodeWebhooks, $sqsData);
                }

            } else {
                if (count($appCodeWebhooks)) {
                    $this->callRegisterWebhook($appCodeWebhooks, $sqsData);
                }

            }
        } else {
            if (count($appCodeWebhooks)) {
                $this->callRegisterWebhook($appCodeWebhooks, $sqsData);
            }

        }
        $this->removeWebhooksNotUsedByApps($shop['remote_shop_id'], $marketplace);
        return true;
    }

    public function callRegisterWebhook($Webhooks, $sqsData)
    {
        $userId = $this->di->getUser()->id;
        $shop = $sqsData['data']['shop'];
        $appCode = $sqsData['data']['appCode'];
        $cursor = $sqsData['data']['cursor'];
        $webhookId = array_keys($Webhooks)[$cursor];
        $registeredWebhookres = $this->registerWebhook($shop, $appCode, $webhookId);

        if (isset($registeredWebhookres['success']) && $registeredWebhookres['success']) {
            if (isset($sqsData['data']['addWebhook'])) {
                $sqsData['data']['addWebhook'][$webhookId]['dynamo_webhook_id'] = $registeredWebhookres['config_save_result']['id'];
            } else {
                $Webhooks[$webhookId]['dynamo_webhook_id'] = $registeredWebhookres['config_save_result']['id'];
                $sqsData['data']['addWebhook'] = $Webhooks;
            }
        }

        $cursor += 1;
        $sqsData['data']['cursor'] = $cursor;
        if (isset(array_keys($Webhooks)[$cursor])) {
            $this->di->getMessageManager()->pushMessage($sqsData);
            return true;
        } else {
            if (!empty($sqsData['data']['addWebhook'])) {
                $this->di
                    ->getObjectManager()
                    ->get('App\Connector\Models\User\Shop')
                    ->addWebhook($shop, $appCode, $sqsData['data']['addWebhook']);
            }

            $this->di->getCache()->delete('marketplaceWebhooks_' . $userId . '_' . $appCode);
            $this->di->getCache()->delete('appsWebhooks_' . $userId . '_' . $appCode);
        }
    }

    public function registerWebhook($shop, $appCode, $webhookId)
    {

        $userId = $this->di->getUser()->id;
        $awsConfig = include BP . DS . 'app' . DS . 'etc' . DS . 'aws.php';

        $webhook = $this->di->getCache()->get('marketplaceWebhooks_' . $userId . '_' . $appCode)[$webhookId]; //marketplace webhooks
        $appWebhook = $this->di->getCache()->get('appsWebhooks_' . $userId . '_' . $appCode)[$webhookId]; //appCode webhooks
        $defaultWebhook = $this->di->getConfig()->webhook->get('default')->get($webhookId) ? $this->di->getConfig()->webhook->get('default')->get($webhookId)->toArray() : ['queue_config_id' => $this->di->getConfig()->queue_config_id]; //default webhooks

        if ($this->di->getConfig()->webhook->get($appCode)) {
            $configAppWebhook = $this->di->getConfig()->webhook->get($appCode)->get($webhookId) ? $this->di->getConfig()->webhook->get($appCode)->get($webhookId)->toArray() : [];
            $appWebhook = array_merge($appWebhook, $configAppWebhook);
        }

        $webhook = array_merge($defaultWebhook, $webhook);
        $webhook = array_merge($webhook, $appWebhook);

        $webhookData = [
            'type' => 'sqs',
            'queue_config' => [
                'region' => $awsConfig['region'],
                'key' => $awsConfig['credentials']['key'],
                'secret' => $awsConfig['credentials']['secret'],
            ],
            'webhook_code' => $webhook['code'],
            'queue_config_id' => $webhook['queue_config_id'] ?? 'sqs-' . $this->di->getConfig()->app_code, // new index
            'format' => 'json',
            'queue_name' => isset($webhook['queue_name']) ? $this->di->getConfig()->app_code . '_' . $webhook['queue_name'] : $this->di->getConfig()->app_code . "_" . $webhookId,
            'queue_data' => [
                'type' => 'full_class',
                'class_name' => $webhook['class_name'] ?? '\App\Connector\Models\SourceModel',
                'method' => $webhook['method'] ?? 'triggerWebhooks',
                'user_id' => $userId,
                'action' => $webhook['action'],
                'queue_name' => isset($webhook['queue_name']) ? $this->di->getConfig()->app_code . '_' . $webhook['queue_name'] : $this->di->getConfig()->app_code . "_" . $webhookId,
                'app_code' => $appCode,
                'marketplace' => $shop['marketplace'] ?? '',
            ],
        ];
        $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($shop['marketplace'])->get('source_model'));

        if (method_exists($model, 'prepareWebhookData')) {
            $model->prepareWebhookData($shop, $appCode, $webhook, $webhookData);
        }

        $responseWbhook = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
            ->init($shop['marketplace'], 'true', $appCode)
            ->call('/webhook/register', [], ['shop_id' => $shop['remote_shop_id'], 'data' => $webhookData, 'webhook' => $webhook], 'POST');

        print_r($responseWbhook);
        return $responseWbhook;
    }

    public function removeWebhooksNotUsedByApps($remoteShopId, $marketplace)
    {
        $user = $this->di->getObjectManager()->create('\App\Core\Models\User\Details')->getUserByRemoteShopId($remoteShopId, ['shops' => 1]);
        if (isset($user['shops'])) {
            $shops = $user['shops'];
            $allWebhooks = [];
            $allSavedWebhhok = [];

            foreach ($shops as $sh) {
                if ($sh['remote_shop_id'] == $remoteShopId) {

                    foreach ($sh['apps'] as $app) {
                        $appCode = $app['code'];
                        $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                            ->init($marketplace, false, $appCode)
                            ->call('/appsWebhooks', [], ['shop_id' => $remoteShopId], 'GET');
                        $webhooks = $remoteResponse['data'];
                        $allWebhooks = [];
                        if (!empty($webhooks)) {
                            foreach ($webhooks as $webhook) {
                                $webhook['marketplace'] = $marketplace;
                                $webhook['app_code'] = $appCode;
                                $webhook['action'] = $webhook['code'];
                                $webhook['queue_name'] = $appCode . '_' . $webhook['code'];
                                $allWebhooks[$webhook['code']] = $webhook;
                            }
                        }
                        if (isset($app['webhooks'])) {
                            $webhooks = $app['webhooks'];

                            foreach ($webhooks as $key => $webhookCode) {
                                $allSavedWebhhok[$webhookCode['code']] = ['savad_data' => $webhookCode, 'app_code' => $appCode];
                            }
                        }
                    }
                    $shop = $sh;
                    break;
                }
            }
        }

        if (!empty($allSavedWebhhok)) {
            $removeWebhooks = array_diff_key($allSavedWebhhok, $allWebhooks);

            if (!empty($removeWebhooks)) {
                $finalRemoveWebhook = [];
                foreach ($removeWebhooks as $webhookId => $savedDataWithAppCode) {
                    $finalRemoveWebhook[$savedDataWithAppCode['app_code']][] = $savedDataWithAppCode['savad_data'];
                }

                foreach ($finalRemoveWebhook as $appCode => $webhook) {
                    $this->removeAppCodeWiseWebhhok($shop, $webhook, $appCode);
                }
            }
        }
    }

    public function removeAppCodeWiseWebhhok($shop, $webhooks, $appCode)
    {
        $userId = $this->di->getUser()->id;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $user = $mongo->getCollectionForTable("user_details");
        $dynamoIds = [];
        $appWiseWebhookIds = [];
        foreach ($webhooks as $appCode1 => $savedData) {
            $appWiseWebhookIds[$appCode][] = $savedData['code'];
            $dynamoIds[] = $savedData['dynamo_webhook_id'];
        }

        foreach ($appWiseWebhookIds as $appCode => $webhookIds) {
            $res = $user->updateOne(
                [
                    "_id" => new \MongoDB\BSON\ObjectId($userId),
                ],
                [
                    '$pull' => [
                        'shops.$[shop].apps.$[app].webhooks' => [
                            'code' => ['$in' => $webhookIds],
                        ],
                    ],
                ],
                [
                    'arrayFilters' => [
                        [
                            'shop.remote_shop_id' => $shop['remote_shop_id'],
                        ],
                        [
                            'app.code' => $appCode,
                        ],
                    ],
                ]

            );
            print_r($res);
        }

        if (!empty($dynamoIds)) {
            $responseWbhook = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                ->init($shop['marketplace'], 'true')
                ->call("/webhooknew/unregister", [], ['shop_id' => $shop['remote_shop_id'], 'dynamoIds' => $dynamoIds], 'DELETE');
            echo "<pre>";
            print_r($responseWbhook);
            return $responseWbhook;
        }
    }

    public function registerUninstallWebhook($shop, $appCode)
    {

        return $this->registerWebhook($shop, $appCode, 'uninstall');
    }
    // Register Webhook code end

    // Product Import code starts
    public function initiateImport($data = [], $userId = null, $force = false)
    {
        if (isset($data['marketplace'])) {

            $marketplace = $data['marketplace'];
            $shopId = $data['shop'] ?? false;
            $userId = $userId ?? $this->di->getUser()->id;

            if (!$userId) {
                return ['success' => false, 'message' => 'Invalid User. Please check your login credentials'];
            }

            if ($marketplace && $shopId) {
                $shop = $this->di->getObjectManager()->get("\App\Core\Models\User\Details")
                    ->getShop($shopId, $userId);
                return $this->checkAndPushToImportQueue($userId, $shop, $data, $force);
            } else {
                $shops = $this->di->getObjectManager()->get("\App\Core\Models\User\Details")
                    ->findShop(
                        [
                            'marketplace' => $marketplace,
                        ],
                        $userId
                    );

                $result = [];
                if (empty($shops)) {
                    return [
                        'success' => false,
                        'message' => 'Marketplace not found, please connect the marketplace first',
                    ];
                }
                foreach ($shops as $shop) {
                    $result[] = $this->checkAndPushToImportQueue($userId, $shop, $data, $force);
                }
                return $result[0] ?? ['success' => false, 'message' => 'Message not given by admin'];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Markeplace is required',
            ];
        }
    }

    public function checkAndPushToImportQueue($userId, $shop, $data, $force)
    {
        $salesChannel = false;
        if (isset($data['saleschannel']) && $data['saleschannel'] == '1') {
            $salesChannel = true;
        }

        $queueData = [
            'user_id' => $userId,
            'message' => 'product import in progress',
            'type' => $salesChannel ? 'saleschannel_product_import' : 'product_import',
            'progress' => 0.00,
            'shop_id' => (string) $shop['_id'],
            'created_at' => date('c'),
            'sales_channel' => $salesChannel,
        ];
        $queuedTask = new \App\Connector\Models\QueuedTasks;
        $queuedTaskId = $queuedTask->setQueuedTask($userId, $queueData);

        if (!$queuedTaskId && !$force) {
            return ['success' => false, 'message' => 'Product Import process is already under progress. Please check notification for updates.'];
        } elseif (isset($queuedTaskId['success']) && !$queuedTaskId['success']) {
            return $queuedTaskId;
        }

        $appCode = $this->di->getAppCode()->get();
        $appTag = $this->di->getAppCode()->getAppTag();

        $handlerData = [
            'type' => 'full_class',
            'appCode'=>$appCode,
            'appTag'=>$appTag,
            'class_name' => '\App\Connector\Components\Route\ProductRequestcontrol',
            'method' => 'handleImport',
            'queue_name' => $shop['marketplace'] . '_product_import',
            'own_weight' => 100,
            'user_id' => $userId,
            'data' => [
                'operation' => 'import_products_tempdb',
                'user_id' => $userId,
                'individual_weight' => 1,
                'feed_id' => $queuedTaskId,
                'shop' => $shop,
            ],
        ];

        if (isset($shop['apps'][0]['code'])) {
            $handlerData['data']['app_code'] = $shop['apps'][0]['code'];
        }

        if (isset($data['target_marketplace']) && $data['target_shop_id']) {
            $handlerData['data']['target_marketplace'] = $data['target_marketplace'];
            $handlerData['data']['target_shop_id'] = $data['target_shop_id'];
        }

        $sourceModel = $this->di->getObjectManager()->get($this->di->getConfig()->connectors
                ->get($shop['marketplace'])->get('source_model'));
        $handlerData = $sourceModel->prepareProductImportQueueData($handlerData, $shop);

        return [
            'success' => true,
            'message' => $shop['marketplace'] . ' Product Import Initiated',
            'queue_sr_no' => $this->di->getMessageManager()->pushMessage($handlerData),
        ];
    }

    public function prepareProductImportQueueData($handlerData, $shop)
    {
        return $handlerData;
    }

    public function getMarketplaceProducts($sqsData)
    {
        return [];
    }

    // Product Import code ends

    public function triggerWebhooks($data)
    {
        $data['arrival_time'] = date('c');
        if (isset($data['user_id'])) {
            $this->di->getObjectManager()->get("\App\Core\Components\Helper")->setUserToken($data['user_id']);
        } else {
            return true;
        }
        $response = $this->initWebhook($data);
        if ($response === 2) {
            return 2;
        } else {
            return true;
        }

    }

    public function initWebhook($data)
    {
        $actionToPerform = $data['action'];
        $getConnectedChannels = $this->getSellerConnectedChannels($data);
        switch ($actionToPerform) {

            case 'app_delete':
                $this->di->getObjectManager()
                    ->get("\App\Connector\Components\Hook")
                    ->TemporarlyUninstall($data);
                break;
            case 'product_create':
                //send request to Connect Channels
                if (!empty($getConnectedChannels)) {
                    foreach ($getConnectedChannels as $moduleHome => $channel) {

                        $class = '\App\\' . $moduleHome . '\Components\Product\Hook';
                        if (class_exists($class)) {
                            if (method_exists($class, 'createQueueToCreateProduct')) {
                                $updatedDatabase = $this->di->getObjectManager()->get($class)->createQueueToCreateProduct($data);
                            } else {
                                $this->di->getLog()->logContent('createQueueToCreateProduct Class found, method not found. ' . json_encode($data), 'info', 'MethodNotFound.log');
                            }
                        } else {
                            $this->di->getLog()->logContent($class . ' Class not found. ' . json_encode($data), 'info', 'ClassNotFound.log');
                        }
                    }
                }

                break;

            case 'product_update':
                //send request to Connect Channels
                if (!empty($getConnectedChannels)) {
                    foreach ($getConnectedChannels as $moduleHome => $channel) {
                        $class = '\App\\' . $moduleHome . '\Components\Product\Hook';
                        if (class_exists($class)) {
                            if (method_exists($class, 'createQueueToUpdateProduct')) {
                                $this->di->getObjectManager()->get($class)->createQueueToUpdateProduct($data);
                            } else {
                                $this->di->getLog()->logContent('createQueueToUpdateProduct Class found, method not found. ' . json_encode($data), 'info', 'MethodNotFound.log');
                            }
                        } else {
                            $this->di->getLog()->logContent($class . ' Class not found. ' . json_encode($data), 'info', 'ClassNotFound.log');
                        }
                    }
                }

                break;

            case 'product_delete':
                //send request to Connect Channels
                if (!empty($getConnectedChannels)) {
                    foreach ($getConnectedChannels as $moduleHome => $channel) {

                        $class = '\App\\' . $moduleHome . '\Components\Product\Hook';
                        if (class_exists($class)) {
                            if (method_exists($class, 'createQueueToDeleteProduct')) {
                                $updatedDatabase = $this->di->getObjectManager()->get($class)->createQueueToDeleteProduct($data);
                            } else {
                                $this->di->getLog()->logContent('createQueueToDeleteProduct Class found, method not found. ' . json_encode($data), 'info', 'MethodNotFound.log');
                            }
                        } else {
                            $this->di->getLog()->logContent($class . ' Class not found. ' . json_encode($data), 'info', 'ClassNotFound.log');
                        }

                    }
                }

                break;
        }

        return true;
    }

    public function getSellerConnectedChannels($data)
    {
        $returnDetails = [];
        $moduleCode = $data['code'];

        $shops = $this->di->getUser()->shops;

        foreach ($shops as $key => $shop) {
            if (is_array($shop)) {
                $moduleHome = ucfirst($shop['marketplace']) . 'home';
                if ($moduleHome != $moduleCode) {
                    $returnDetails[ucfirst($shop['marketplace'])] = $shop;
                }
            }
        }
        return $returnDetails;
    }

    public function checkCurrency($userId, $targetMarketplace, $sourceMarketplace, $targetMarketplaceShopId = false)
    {

        $sourceMarketplaceCurrency = false;
        $targetMarketplaceCurrency = false;

        $user_details = $this->di->getObjectManager()->get('\App\Core\Models\User\Details');
        $shops = $user_details->getUserDetailsByKey('shops', $userId);

        foreach ($shops as $key => $shop) {
            $currency = $shop['currency'] ?? "";
            if ($currency) {
                if ($shop['marketplace'] == $sourceMarketplace) {
                    $sourceMarketplaceCurrency = $currency;
                } else {
                    if ($targetMarketplaceShopId) {
                        if ($shop['marketplace'] == $targetMarketplace && $targetMarketplaceShopId == $shop['_id']) {
                            $targetMarketplaceCurrency = $shop['currency'];
                        }
                    } else {
                        if ($shop['marketplace'] == $targetMarketplace) {
                            $targetMarketplaceCurrency = $shop['currency'];
                        }
                    }
                }
            }
        }

        /*$baseMongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $configuration = $baseMongo->getCollectionForTable('configuration')->findOne(['user_id' => $userId], ["typeMap" => ['root' => 'array', 'document' => 'array']]);

        print_r($configuration);
        die;*/
        /*if (isset($configuration['data']['currency_settings']['settings_enabled']) && $configuration['data']['currency_settings']['settings_enabled']) {
        if (isset($configuration['data']['currency_settings']['amazon_currency'])) {
        $changeCurrency = $configuration['data']['currency_settings']['amazon_currency'];
        }
        }
        $commonHelper = $this->di->getObjectManager()->get(Helper::class);
        $connectedAccounts = $commonHelper->getAllShopsToConnect($userId, $targetMarketplace, $targetMarketplaceShopId);

        foreach ($connectedAccounts as $account) {
        if ($account['warehouses'][0]['status'] == 'active') {

        if (!$changeCurrency) {
        if ($targetMarketplaceCurrency != $sourceMarketplaceCurrency) {
        return false;
        } else {
        $changeCurrency = 1;
        }
        }
        }
        }*/
        return ($sourceMarketplaceCurrency === $targetMarketplaceCurrency);
    }

    /**
     * @param $data
     * @return array
     */
    public function uploadProducts($data)
    {
        $userId = isset($data['user_id']) ? $data['user_id'] : $this->di->getUser()->id;
        if (!$userId) {
            return ['success' => false, 'message' => 'Invalid User Id'];
        }

        if (!isset($data['target_marketplace']['marketplace']) || !isset($data['source_marketplace']['marketplace']) || !isset($data['target_marketplace']['shop_id']) || !isset($data['source_marketplace']['shop_id'])) {
            return ['sucess' => false, 'message' => 'Invalid request'];
        }

        $targetShopIds = $data['target_marketplace']['shop_id'];
        foreach ($targetShopIds as $shopKey => $shop_id) {
            $changeCurrency = $this->checkCurrency($userId, $data['target_marketplace']['marketplace'], $data['source_marketplace']['marketplace'], $shop_id);
            if (!$changeCurrency) {
                unset($targetShopIds[$shopKey]);
            }
        }

        if (empty($targetShopIds)) {
            return ['status' => false, 'message' => 'Unable to upload product due to currency of ' . ucfirst($data['source_marketplace']['marketplace']) . ' and ' . ucfirst($data['target_marketplace']['marketplace']) . ' accounts are not same.'];
        }

        $data['target_marketplace']['shop_id'] = $targetShopIds;

        $targetSourceModel = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($data['target_marketplace']['marketplace'])->get('source_model'));

        if (method_exists($targetSourceModel, 'productUpload')) {
            $responcefromTargetSourceModel = $targetSourceModel->productUpload($data);
            if (isset($responcefromTargetSourceModel['success']) && $responcefromTargetSourceModel['success']) {
                return ['success' => true, 'message' => $responcefromTargetSourceModel['message']];
            } elseif (isset($responcefromTargetSourceModel['success']) && !$responcefromTargetSourceModel['success']) {
                return ['success' => false, 'message' => $responcefromTargetSourceModel['message']];
            } else {
                $success_msg = $error_msg = '';
                foreach ($responcefromTargetSourceModel as $response) {
                    if (isset($response['success']) && $response['success']) {
                        $success_msg .= $response['message'];
                    } elseif (isset($response['success']) && !$response['success']) {
                        $error_msg .= $response['message'];
                    }
                }
                if ($success_msg) {
                    return ['success' => true, 'message' => $success_msg];
                } elseif ($error_msg) {
                    return ['success' => false, 'message' => $error_msg];
                } else {
                    return ['success' => false, 'message' => 'Error while uploading your products . please contact us'];
                }
            }

        } else {
            return ['success' => false, 'message' => 'Upload method doesn\'t exists on ' . ucfirst($data['target_marketplace']['marketplace'])];
        }

    }

    public function disconnectAccount($data, $user_id = false)
    {

        $eventsManager = $this->di->getEventsManager();

        if (isset($data['target_marketplace']['shop_id']) && isset($data['target_marketplace']['marketplace'])) {
            if (!$user_id) {
                $user_id = $this->di->getUser()->id;
            }

            $shopId = $data['target_marketplace']['shop_id'];
            $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
            $collection = $mongo->getCollection(self::USER_DETAILS_CONTAINER);
            $shopData = [];
            $userDetails = $this->di->getObjectManager()->create('\App\Core\Models\User\Details');
            $shopData = $userDetails->getShop($shopId, $user_id);
            $targetMarketplace = $data['target_marketplace']['marketplace'];
            $sourceMarketplace = $data['source_marketplace']['marketplace'];
            $remote_shop_id = $shopData['remote_shop_id'];
            $eventsManager->fire('application:beforeDisconnect', $this, ['custom_data' => &$shopData]);
            
            //for deletion of product
            $productContainer = $mongo->getCollection(self::PRODUCT_CONTAINER);
            $response = $productContainer->deleteMany([
                'user_id' => $user_id,
                "target_marketplace" => $data['target_marketplace']['marketplace'],
                'shop_id' => $data['target_marketplace']['shop_id'],
            ], ['w' => true]);

            //report_Contatiner
            $reportContainer = $mongo->getCollection(self::REPORT_CONTAINER);
            $reportContainerResponse = $reportContainer->deleteMany([
                'user_id' => $user_id,
                'shop_id' => $data['target_marketplace']['shop_id'],
            ], ['w' => true]);
            //queuid_task
            $queuedTask = $mongo->getCollection(self::QUEUED_TASKS);
            $aggregate = [];
         
            $queuedTaskResponse = $queuedTask->deleteMany([
                'user_id' => $user_id,
                'shop_id' => $data['target_marketplace']['shop_id'],
            ], ['w' => true]);
           
            //user_details
            $result = $collection->UpdateOne(
                ['user_id' => (string) $user_id],
                ['$pull' => ["shops" => ['_id' => $shopId]]]
            );
            //remote shop
            $appCode = $this->di->getAppCode()->get();
            if (isset($appCode[$sourceMarketplace])) {
                $remoteResponse = $this->di->getObjectManager()->get("\App\Connector\Components\ApiClient")
                    ->init($targetMarketplace, false, $appCode[$targetMarketplace])
                    ->call('app-shop', [], ['shop_id' => $remote_shop_id], 'DELETE');
            }
            if (isset($shopId) && !empty($shopData)) {
                return ['success' => true, "message" => "Account deleted successfully"];
            } else {
                return ['success' => false, "message" => "Shop not found"];
            }
        }
    }
}
