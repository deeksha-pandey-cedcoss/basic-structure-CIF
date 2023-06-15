<?php

use Phalcon\Cli\Task;

class CacheTask extends Task
{

    public function flushAction(){
		$this->getDi()->getCache()->flush();
		echo 'Cache has been flushed'. PHP_EOL;
    }

}
