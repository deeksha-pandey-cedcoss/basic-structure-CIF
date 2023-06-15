<?php

namespace App\Core\Controllers;

class TokenController extends BaseController
{
    public function getAction()
    {
        $userId = $this->di->getUser()->id;
        $pageSettings = $this->request->get();
        if ( !isset($pageSettings['count']) ) $pageSettings['count'] = 500;
        if ( !isset($pageSettings['activePage']) ) $pageSettings['activePage'] = 1;
        $userGeneratedTokens = new \App\Core\Models\UserGeneratedTokens;
        try {
            $subUserID = $this->di->getUser()->getSubUserId();
        } catch (\Exception $e) {
            print_r($e);die;
        }
        return $this->prepareResponse($userGeneratedTokens->getUserGeneratedTokens($userId, $pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count'], $subUserID));
    }

    public function createAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        try {
            $subUserID = $this->di->getUser()->getSubUserId();
        } catch (\Exception $e) {
            print_r($e);die;
        }
        if ( isset($subUserID) && $subUserID !== 0 ) {
            $rawBody['user'] = $subUserID;
        }
        $userGeneratedTokens = new \App\Core\Models\UserGeneratedTokens;
        return $this->prepareResponse($userGeneratedTokens->createToken($rawBody,false));
    }

    public function removeAction()
    {
        $tokenId = $pageSettings = $this->request->get('token_id');
        $token = ['token_id' => $tokenId, 'user_id' => $this->di->getUser()->id];
        if($this->di->getObjectManager()->get('App\Core\Components\TokenManager')->removeToken($token)){
            $response = ['success'=>true];
        }else{
            $response = ['success'=>false,'code'=>'unable_to_remove_the_token'];
        }
        try {
            $subUserID = $this->di->getUser()->getSubUserId();
        } catch (\Exception $e) {
            print_r($e);die;
        }
        if ( isset($subUserID) && $subUserID !== 0 ) {
            $response['user'] = $subUserID;
        }
        return $this->prepareResponse($response);
    }

    /**
     * Generate a new token from the Refresh Token for user.
     * @input $refreshToken
     * @return New user Token
     */
    public function getTokenByRefreshAction(){
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        
        $userGeneratedTokens = new \App\Core\Models\UserGeneratedTokens;
        return $this->prepareResponse($userGeneratedTokens->createTokenByRefresh($rawBody));
    }
}
