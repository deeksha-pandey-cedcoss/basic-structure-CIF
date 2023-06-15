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
 * @package     Ced_amazon
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright © 2018 CedCommerce. All rights reserved.
 * @license     EULA http://cedcommerce.com/license-agreement.txt
 */

namespace App\Connector\Controllers;

class WebhookController extends \App\Core\Controllers\BaseController
{
    public function triggerAction(){
        $source = "";
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $params = $this->request->getJsonRawBody(true);
        } else {
            $params = $this->di->getRequest()->get();
        }

        if(isset($params['source'])) $source = $params['source'];
        if(empty($source))  return $this->prepareResponse(['success' => true, 'code' => 'invalid_parameters', 'message' => 'Action Aborted. Please pass source value to create Webhooks.']);

        $appCode = $this->di->getAppCode()->get();
        $appTag = $this->di->getAppCode()->getAppTag();
        $appCode=$source; //for test
        if(empty($appCode) || empty($appTag)) return $this->prepareResponse(['success' => true, 'code' => 'invalid_parameters', 'message' => 'Action Aborted. Please pass AppTag & AppCode values to create Webhooks.']);

        $userDetails = $this->di->getObjectManager()->get('\App\Core\Models\User\Details');
        $shops = $userDetails->getDataByUserID($this->di->getUser()->id, $source);
        /* Register Marketplace  Webhooks with Source */
        $createWebhookRequest = $this->di->getObjectManager()->get("\App\Connector\Models\SourceModel")->routeRegisterWebhooks($shops,$source,$appCode);
        return $this->prepareResponse($createWebhookRequest);
    }


    // public function triggerAction(){
    //     $this->di->getLog()->logContent('webhook triggered ...','info','initwebhook.log');
    //     $this->di->getObjectManager()->get("\App\Shopifyhome\Models\SourceModel")->registerWebhooks();
    //     $this->di->getObjectManager()->get("\App\Facebookhome\Models\SourceModel")->registerWebhooks();
    // }
}