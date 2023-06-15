<?php
namespace App\Core\Components;

use \App\Core\Models\TokenManager as TaskManager;

class TokenManager extends Base
{

    public function setDi(\Phalcon\Di\DiInterface $di):void
    {
        parent::setDi($di);
    }

    public function addToken(&$token)
    {
        $tokens = new TaskManager;
        $dummyToken = $token;
        if(isset($dummyToken['exp'])){
            $dummyToken['expd'] = new \MongoDB\BSON\UTCDateTime($dummyToken['exp']*1000);
            $dummyToken['exp'] = (new \DateTime())->setTimestamp($dummyToken['exp'])->format('c');

        }
            
        $dummyToken['created_at'] = new \MongoDB\BSON\UTCDateTime();
        $dummyToken['used_at'] = new \MongoDB\BSON\UTCDateTime();
        if(is_string($dummyToken['user_id'])){
            $dummyToken['user_id'] = $tokens->getObjectId($dummyToken['user_id']);
        }
        $tokens->setData($dummyToken);
        $tokens->save();
        $token['token_id'] = (string)$tokens->_id;
        
        $this->addTokenToCache($token);
    }

    public function addTokenToCache($token)
    {
        //$tokens = $this->getTokensFromCache($token['user_id']);
        $tokens = [];
        $tokens[$token['token_id']] = $token['token_id'];
        $this->di->getCache()->set('token_manager_'.$token['user_id'], $tokens);
    }

    public function getTokensFromCache($userId)
    {
        return $this->di->getCache()->get('token_manager_'.$userId);
    }

    public function getTokenFromCache($userId, $tokenId)
    {
        $tokens = $this->getTokensFromCache($userId);
       // print_r($tokens);die("id01");
        return $tokens[$tokenId] ?? false;
    }

    public function deleteTokenFromCache($token)
    { 
        $tokens = $this->getTokensFromCache($token['user_id']);
        if (isset($tokens[$token['token_id']])) {
            unset($tokens[$token['token_id']]);
        }
        $this->di->getCache()->set('token_manager_'.$token['user_id'], $tokens);
    }

    public function checkToken($token)
    {
        if (isset($token['user_id']) && isset($token['token_id'])) {
            if ($tokenId = $this->getTokenFromCache($token['user_id'], $token['token_id'])) {
                if ($token['token_id'] == $tokenId) {
                    return true;
                }
            } else {

                //$tokens = TaskManager::findFirst(['user_id' => TaskManager::getObjectId($token['user_id']), '_id' => TaskManager::getObjectId($token['token_id'])]);
                $tokens = TaskManager::findFirst([[/*'user_id' => TaskManager::getObjectId($token['user_id']), */'_id' => TaskManager::getObjectId($token['token_id'])]]);
                 
                /*$tokens = TaskManager::findFirst("user_id='{$token['user_id']}' AND token_id='{$token['token_id']}'");*/
                if ($tokens) {
                    $this->addTokenToCache($token);
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function removeToken($token)
    {
        if (isset($token['user_id']) && isset($token['token_id'])) {
            $this->deleteTokenFromCache($token);
            $token = TaskManager::findFirst(["_id" => $token['token_id']]);
            //$tokens = TaskManager::findFirst("user_id='{$token['user_id']}' AND token_id='{$token['token_id']}'");
            if ($token) {
                $token->delete();
            }
            return true;
        } else {
            return false;
        }
    }

    public function disableUserToken($db, $user_id)
    {
        if ($user_id) {
            $connection = $this->di->get($db);
            $connection->setCollection('token_manager')->delete(["user_id" => $user_id]);
            $this->di->getCache()->delete('token_manager_'.$user_id);
        }
    }

    public function cleanExpiredTokens(){
        $collection = $this->di->getObjectManager()->get('\App\Core\Models\TokenManager')->getCollection('token_manager');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $tokens = $collection->find([],$options);
        foreach($tokens as $token){
           
            if(isset($token['exp'])){
                 //print_r($token);die;
                $tokenTimeStamp = (new \DateTime($token['exp']))->getTimeStamp();
                $currentTimeStamp = (new \DateTime())->getTimeStamp();
                if($tokenTimeStamp < $currentTimeStamp){
                   
                    $t = TaskManager::findFirst([['_id' => $token['_id']]]);
                    $t->delete();
                 
                }
                
            }
            
        }



        $collection = $this->di->getObjectManager()->get('\App\Core\Models\UserGeneratedTokens')->getCollection('user_generated_tokens');
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $tokens = $collection->find([],$options);
        foreach($tokens as $token){
           
            if(isset($token['exp'])){

                $tokenTimeStamp = (new \DateTime($token['exp']))->getTimeStamp();
                $currentTimeStamp = (new \DateTime())->getTimeStamp();
                if($tokenTimeStamp < $currentTimeStamp){
                   
                    $t = \App\Core\Models\UserGeneratedTokens::findFirst([['_id' => $token['_id']]]);
                    $t->delete();
                    
                 
                }
                
            }
            
        }
        
        echo 'Cleaned Expired Tokens...';
        /*$result =  $collection->find([ "exp" => ['$lt' => new \MongoDB\BSON\UTCDateTime() ]],$options);
        print_r($result);*/
    }
}
