<?php

use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class FeedTask extends Task
{

    public function updateAllFeedsAction()
    {
        $helper = $this->di->getObjectManager()->get('App\Core\Components\Helper');
        $url = $this->di->getConfig()->backend_base_url.'engine/feed/updateAllFeedsOnEngine';
        var_dump($helper->curlRequest($url,[],false,true,'GET',0));
    }

}