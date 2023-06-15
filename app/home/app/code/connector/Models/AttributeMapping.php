<?php

namespace App\Connector\Models;

use App\Core\Models\Base;

class AttributeMapping extends Base
{
    protected $table = 'mapping';

    public function initialize()
    {
        $this->setSource($this->table);
        $this->setConnectionService($this->getMultipleDbManager()->getDefaultDb());
    }
}
