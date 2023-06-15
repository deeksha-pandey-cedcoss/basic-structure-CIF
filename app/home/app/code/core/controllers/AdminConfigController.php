<?php

namespace App\Core\Controllers;

class AdminConfigController extends BaseController
{
    public function getAllConfigAction()
    {
        $config = new \App\Core\Models\Config;
        return $this->prepareResponse($config->getAllConfig($this->request->get('framework')));
    }

    public function saveConfigAction()
    {
        $config = new \App\Core\Models\Config;
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        return $this->prepareResponse($config->saveConfig($rawBody));
    }
}
