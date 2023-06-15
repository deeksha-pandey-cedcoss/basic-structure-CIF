<?php

use Phalcon\Cli\Task;

class MainTask extends Base
{
    public function mainAction()
    {
        echo 'List of available commands.'
            .PHP_EOL.$this->getCs('setup status','green')
            .PHP_EOL.$this->getCs('setup upgrade','green')
            .PHP_EOL.$this->getCs('setup enable module_name','green')
            .PHP_EOL.$this->getCs('setup disable module_name','green')
            .PHP_EOL.$this->getCs('cache flush','green')
            .PHP_EOL.$this->getCs('cache flush hash_name','green')
            .PHP_EOL.$this->getCs('cache flush all','green')
            .PHP_EOL;
    }

}