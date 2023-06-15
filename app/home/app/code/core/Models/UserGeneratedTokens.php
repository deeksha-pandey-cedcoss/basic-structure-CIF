<?php
namespace App\Core\Models;

class UserGeneratedTokens extends BaseMongo
{
    protected $table = 'user_generated_tokens';

    public function initialize()
    {
        $this->setSource($this->table);

        //$this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
    }

    public function getUserGeneratedTokens($userId, $limit = 500, $activePage = 1, $sub_user_id = 0) {
        if ( $sub_user_id == 0 ) {
            $tokens = UserGeneratedTokens::find([["user_id"=>$userId], 'limit' => $limit, 'offset' => $activePage]);
            $count = UserGeneratedTokens::count([["user_id"=>$userId]]);
        } else {
            $tokens = UserGeneratedTokens::find([["user_id"=>$userId,"subuser_id"=>$sub_user_id], 'limit' => $limit, 'offset' => $activePage]);
            $count = UserGeneratedTokens::count([["user_id"=>$userId,"subuser_id"=>$sub_user_id]]);
        }
        
        return ['success' => true, 'data' => [ 'rows' => $tokens, 'count' => $count]];
    }

    /**
     * Generate a token for the user.
     * @param array $tokenDetails                                                    
     * @return array
     */
    public function createToken($tokenDetails,$restrictForSameUser = true)
    {

        try{
            $userGeneratedToken = new UserGeneratedTokens();



            $allowedRoles = ['refresh' => 'refresh', 'customer_api' => 'customer_api'];


            $userGeneratedToken->user_id = $this->di->getUser()->id;
            $userGeneratedToken->title = $tokenDetails['title'];


            $token = [
                'user_id' => $this->di->getUser()->id,
                'role' => $allowedRoles[$tokenDetails['type']] ?? 'customer_api'
            ];

            $Expiry = true;
            if (isset($tokenDetails['duration'])) {
                if ($tokenDetails['duration'] == 0 || $tokenDetails['duration'] > 9999999) {
                    $Expiry = false;
                }
            } else {
                $tokenDetails['duration'] = 1440;
            }

            if ($Expiry) {
                $date = new \DateTime('+' . $tokenDetails['duration'] . ' days');
                $token['exp'] = $date->getTimestamp();
            }else{
                $date = new \DateTime('+2 hour');
                $token['exp'] = $date->getTimestamp();
            }

            if (isset($tokenDetails['user']) && $tokenDetails['user'] != 'For Self') {
                $token['child_id'] = $tokenDetails['user'];
                $userGeneratedToken->subuser_id = $tokenDetails['user'];
            }

            if (!isset($tokenDetails['domain'])) {
                $tokenDetails['domain'] = $_SERVER['REMOTE_ADDR'];
            }
            $userGeneratedToken->domain = $tokenDetails['domain'];
//            $token['aud'] = $tokenDetails['domain'];
            $helper = $this->di->getObjectManager()->get('App\Core\Components\Helper');


            $eventsManager = $this->di->getEventsManager();
            $eventsManager->fire('token:createBefore',
                $this,
                ['token_details' => $tokenDetails, 'user_token_object'=>$userGeneratedToken,'token'=> &$token]
            );
//            $token['aud'] = $userGeneratedToken->domain; /* setting domain if updated from observer */

            $userGeneratedToken->token = $helper->getJwtToken(
                $token,
                'RS256', 
                true, 
                false, 
                $restrictForSameUser
            );
            $generatedToken = $helper->decodeToken($userGeneratedToken->token);
            $generatedToken = $generatedToken['data'];
            $userGeneratedToken->exp = (new \DateTime())->setTimestamp($generatedToken['exp'])->format('Y-m-d H:i:s');
            $userGeneratedToken->token_id = $generatedToken['token_id'];
            $userGeneratedToken->type = $token['role'];



            $status = $userGeneratedToken->save();
//            $this->di->getLog()->logContent('create token : . '.json_encode($status), 'info', 'TokenData.log');
            if ($status) {
                return ['success' => true, 'message' => 'Token created successfully', 'data' => ['token' => $userGeneratedToken->token]];
            } else {
                $errors = '';
                foreach ($userGeneratedToken->getMessages() as $key => $value) {
                    $errors .= $value;
                }
                return ['success' => false, 'message' => $errors];
            }
        }
        catch (\Exception $e){
            return ['success' => false, 'message' => $e->getMessage()];
        }


    }

    /**
     * Generate a new token from the Refresh Token for user.
     * @param array $RefreshToken                                                    
     * @return array New user Token
     */
    public function createTokenByRefresh($refreshToken)
    {
        $tokenDetails = [];
        $tokenDetails['title'] = $refreshToken['title'] ?? 'Auto generated Token';
        $tokenDetails['type'] = 'customer_api';
        $tokenDetails['duration'] = $refreshToken['duration'] ?? 1440;
        $tokenDetails['user'] = 'For Self';
        return $this->createToken($tokenDetails);
    }
}
