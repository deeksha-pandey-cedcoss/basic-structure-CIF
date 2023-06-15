<?php

use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class TokenTask extends Task
{   
    protected $_args = false;

    public function cleanAction()
    {
        
        $manager = $this->di->getObjectManager()->get('\App\Core\Components\TokenManager');
        $manager->cleanExpiredTokens();
    }
}