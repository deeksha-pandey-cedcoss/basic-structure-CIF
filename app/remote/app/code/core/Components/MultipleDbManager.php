<?php

namespace App\Core\Components;

use App\Core\Models\UserDb;

class MultipleDbManager extends Base
{
    public function getDb()
    {
        /*if ($this->di->getRegistry()) {
            $token = $this->di->getRegistry()->getDecodedToken();
            $userDb = UserDb::findFirst("id='{$token['user_id']}'");
            if ($userDb) {
                return $userDb->db;
            }
        }*/
        return 'db';
    }

    public function getDefaultDb()
    {
        return $this->di->getConfig()->default_db;
    }

    public function getCurrentDb()
    {
        return $this->di->getConfig()->current_db;
    }
}
