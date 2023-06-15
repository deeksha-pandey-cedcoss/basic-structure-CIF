<?php

use App\Rmq\Components\App\DeploymentConfig;
use App\Rmq\Components\MessageQueue\EnvelopeFactory;
use App\Rmq\Components\Config;
use App\Rmq\Components\Rqueue;
use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class WorkerTask extends Task
{

    public function addAction() 
    {
        global $argv;
        
        if(isset($argv[3]) && $argv['1']=='worker' && $argv[2]=='add'){
            
            $queueName = $argv[3];
            $deploymentConfig = new DeploymentConfig();
            $config = new Config($deploymentConfig);
            $envelopeFactory = new EnvelopeFactory();
            $queue = new Rqueue($config,$envelopeFactory,$queueName,$this->di);
            $queue->subscribe();
        }
       
        
        
    }

    public function addSqsAction()
    {
        global $argv;
        if(isset($argv[3]) && $argv['1']=='worker' && $argv[2]=='addSqs'){
            $queueName = $argv[3];
            $this->di->getObjectManager()->get('\App\Core\Components\Message\Handler\Sqs')->consume($queueName,true);

            die;
            $queueName = $argv[3];
            $deploymentConfig = new DeploymentConfig();
            $config = new Config($deploymentConfig);
            $envelopeFactory = new EnvelopeFactory();
            $queue = new Rqueue($config,$envelopeFactory,$queueName,$this->di);
            $queue->subscribe();
        }
    }
}