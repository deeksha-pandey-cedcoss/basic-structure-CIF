<?php

use Phalcon\Cli\Task;


class OrderTask extends Task
{   
    protected $_args = false;

    public function initiateSyncAction()
    {
        $objectManager = $this->di->getObjectManager();
        $helper = $objectManager->get('\App\Connector\Components\OrderHelper');
        $helper->queueAllShopsToSyncOrder();
    }

    // This one is for Ebay
    public function initiateOrderSyncAction()
    {
        $objectManager = $this->di->getObjectManager();
        $helper = $objectManager->get('\App\Connector\Components\OrderHelper');
        $helper->queueShopsToSyncOrder();
    }
}