<?php
use Phalcon\Cli\Task;

/**
 * CacheTask
 */
class CacheTask extends Task
{
    
    /**
     * flushAction
     *
     * @return void
     */
    public function flushAction()
    {
        global $argv;
        $this->getDi()->getCache()->flush();
        echo 'Cache has been flushed'. PHP_EOL;

    }    
    /**
     * listAction
     *
     * @return void
     */
    public function listAction()
    {
        foreach ($this->getDi()->getCache()->getAll() as $key){
            echo $key.PHP_EOL;
        }

    }

    /**
     * Php app/cli cache clean                                                 
     * This will delete all expired token from Database.
     */
    public function cleanAction(){
    	$user_db = $this->di->getObjectManager()->get('\App\Core\Components\MultipleDbManager')->getDefaultDb();
        $connection = $this->di->get($user_db);
        $sql = "DELETE from `token_manager` WHERE `exp` < NOW()";
        $connection->execute($sql);
		echo 'Old token has been cleaned'. PHP_EOL;
    }

}
