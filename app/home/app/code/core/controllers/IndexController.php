<?php
namespace App\Core\Controllers;

use Phalcon\Mvc\Controller;
use \Firebase\JWT\JWT;
use Phalcon\Di;

class IndexController extends Controller
{
    public function indexAction()
    {
        
        
        echo $this->url->getBaseUri().'<br>';
        ;
        echo $this->router->getControllerName().'<br>';
        ;
        echo $this->router->getActionName().'<br>';
        ;
        echo '<h1>Hello1121!</h1>';
    }

    public function testAction()
    {
        $user = $this->di->getObjectManager()->create('\App\Core\Models\User');
        $data = $user->login([
                        'username'=>'app',
                        'email'=>'ankurverma@cedcoss.com',
                        'password'=>'password123'
                    ]);
        print_r($data);
        die;
        /**
         * openssl genrsa -out connector.pem 2048
         * openssl rsa -in connector.pem -pubout > connector.pub
         **/
        $dir = BP.DS.'app'.DS.'etc'.DS.'security'.DS;
        $privateKey = file_get_contents($dir.'connector.pem');
        $publicKey = file_get_contents($dir.'connector.pub');

        $token = array(
            "iss" => "example.org",//issuer
            "aud" => "example.com",//audience
            "iat" => 1356999524, //initiated at
            "nbf" => 1356999523, // not before,
            "user_id" => 1,
        );

         $jwt = JWT::encode($token, $privateKey, 'RS256');
        $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($jwt);
       //  $jwt = JWT::encode($token, $privateKey, 'HS256');
        $data = explode('.', $jwt);
        //print_r(base64_decode($data[0]));
        $decodedData = json_decode(base64_decode($data[1]), true);
        $decodedData['user_id'] = 2;
        $decodedData= json_encode($token = array(
            "iss" => "example.org",//issuer
            "aud" => "example.com",//audience
            "iat" => 1356999524, //initiated at
            "nbf" => 1356999523, // not before,
            "user_id" => 1,
        ));
        $decodedData = base64_encode($decodedData);
        //print_r($decodedData);
        //print_r(base64_decode($data[2]));die;
        $decoded = JWT::decode($data[0].'.'.$decodedData.'.'.$data[2], $publicKey, array('RS256'));
        print_r($decoded);
        die;
        die;
        /*
         NOTE: This will now be an object instead of an associative array. To get
         an associative array, you will need to cast it as such:
        */

        $decoded_array = (array) $decoded;
        print_r($decoded_array);
        die;
        // Resolve the service (NOTE: $myClass->setDi($di) is automatically called)
        $objectManager = $this->di->getObjectManager();
        echo $this->router->getControllerName().'<br>';
        ;
        echo $this->router->getActionName().'<br>';
        ;
        $config = $objectManager->create('\App\Core\Models\Config');
        var_dump('dsfdsf');
        //$objectManager->get('\MyInterface')->createRoute();
        return 'test123';
    }
    public function testTestAction()
    {
        // Resolve the service (NOTE: $myClass->setDi($di) is automatically called)
        $objectManager = $this->di->getObjectManager();
        //$objectManager->get('\MyInterface')->createRoute();
        return 'test1WAESDR3';
    }
}
