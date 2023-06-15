<?php

namespace App\Connector\Controllers;

use Phalcon\Mvc\Controller;
use App\Connector\Models\ProductContainer;
use App\Connector\Models\Product;
use Phalcon\Di;
use App\Core\Controllers\BaseController;

class ConfigController extends BaseController
{
    public function saveAction()
    {
        if ($this->request->get()) {
            foreach ($this->request->get('config') as $framework => $data) {
                foreach ($data as $key => $value) {
                    $this->di->getObjectManager()->get('App\Core\Models\User')
                        ->load($this->di->getUser()->getId())->setConfig($key, $value, $framework);
                }
            }
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => 'Config saved successfully', 'data' => '']);
        }
    }
}
