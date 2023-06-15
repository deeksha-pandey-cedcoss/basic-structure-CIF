<?php

namespace App\Connector\Components;

use \Firebase\JWT\JWT;

class ProductHelper extends \App\Core\Components\Base
{
    // function __construct()
    // {
    //     // die("got it");
    // }
    public function uploadProducts($data){
        $success = true;
        $message = 'Product Upload process initiated.';
        if (isset($data['product_ids']) && !empty($data['product_ids'])) {
            // $userId = $userId ?? $this->di->getUser()->id;
            // print_r($userId);
            // die("ds");

        }else{
            return ['success' => false, 'message' => 'something went wrong'];
        }
    }
}