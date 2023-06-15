<?php

namespace App\Connector\Controllers;

class OrderController extends \App\Core\Controllers\BaseController
{
    public function getOrdersAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $ordersModel = new \App\Connector\Models\Order;
        $responseData = $ordersModel->getOrders($rawBody);
        return $this->prepareResponse($responseData);
    }

    public function createOrderAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $orderData = $this->di->getObjectManager()->get('\App\Connector\Components\OrderHelper')->getOrderInRightFormat($this->di->getUser()->id, $rawBody);
        $orderModel = $this->di->getObjectManager()->create('\App\Connector\Models\Order');
        return $this->prepareResponse($orderModel->createOrder($orderData));
    }

    public function getOrderAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $ordersModel = new \App\Connector\Models\OrderContainer;
        $responseData = $ordersModel->getOrderByID($rawBody);
        return $this->prepareResponse($responseData);
    }

    public function getAllOrdersAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $ordersModel = new \App\Connector\Models\OrderContainer;
        $responseData = $ordersModel->getOrders($rawBody);
        return $this->prepareResponse($responseData);
    }

    public function fullfillOrderAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $ordersModel = new \App\Connector\Models\Order;
        $fullfilmentdata = $ordersModel->getfulfillmentdetails($rawBody);
        if ($fullfilmentdata==false) {
            return $this->prepareResponse(['status'=>false,'code'=>'Order not created at Shopify','message'=>'Order not created at Shopify']);
        } else {
            $rawBody['order_data']['fulfillment_data']=$fullfilmentdata;
            $responseData=$this->di->getObjectManager()->get('\App\Connector\Components\OrderHelper')->fullfillOrderToTarget($rawBody);

            return $this->prepareResponse($responseData);
        }
    }

    public function getOrderByIdAction()
    {
        $ordersModel = new \App\Connector\Models\Order();
        $responseData = $ordersModel->getOrderById($this->request->get());
        return $this->prepareResponse($responseData);
    }

    public function uploadOrderAction()
    {
        $ordersModel = new \App\Connector\Models\Order();
        $responseData = $ordersModel->uploadOrder($this->request->get());
        return $this->prepareResponse($responseData);
    }

    public function updateOrderStatusAction()
    {
        $ordersModel = new \App\Connector\Models\Order();
        $responseData = $ordersModel->updateOrderStatus($this->request->get());
        return $this->prepareResponse($responseData);
    }

    public function syncAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        return $this->prepareResponse($this->di->getObjectManager()->get('App\Connector\Components\OrderHelper')->initiateOrderSync($rawBody));
    }

    public function cancelAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $responseData=$this->di->getObjectManager()->get('\App\Connector\Components\OrderHelper')->cancelOrderToTarget($this->di->getUser()->id, $rawBody);
        return $this->prepareResponse($responseData);
    }
    public function cancelItemAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $responseData=$this->di->getObjectManager()->get('\App\Connector\Components\OrderHelper')->cancelItemToTarget($this->di->getUser()->id, $rawBody);
        return $this->prepareResponse($responseData);
    }

    /**
     * Create Order Action
     * @return string
     */
    public function importAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        
        $order = $this->di->getObjectManager()->create('\App\Connector\Models\OrderContainer');
        return $this->prepareResponse($order->importOrders($rawBody));
    }

    /**
     * Upload Order Action
     * @return string
     */
    public function uploadAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        
        $order = $this->di->getObjectManager()->create('\App\Connector\Models\OrderContainer');
        return $this->prepareResponse($order->uploadOrders($rawBody));
    }

    public function selectAndSyncAction(){
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $orderContainer = new \App\Connector\Models\OrderContainer;
        return $this->prepareResponse($orderContainer->syncData($rawBody));
    }
}
