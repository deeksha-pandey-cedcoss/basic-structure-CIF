<?php

namespace App\Core\Controllers;

class AppController extends BaseController
{
    public function createAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->createUser($this->request->get(), 'app'));
    }


    public function loginAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->login($this->request->get(), 'app'));
    }

    public function reportAction() {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->reportIssue($rawBody));
    }

    public function sendOtpAction() {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $user = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
        return $this->prepareResponse($user->sendOtp($rawBody));
    }

    public function matchOtpAction() {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $user = $this->di->getObjectManager()->get('\App\Core\Components\Helper');
        return $this->prepareResponse($user->matchOtp($rawBody));
    }
}
