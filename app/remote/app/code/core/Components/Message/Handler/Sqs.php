<?php

namespace App\Core\Components\Message\Handler;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use http\Exception;
use \Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;

class Sqs extends \App\Core\Components\Base
{
    public $client = false;

    public function updateProgress($id, $points)
    {
        $message = \App\Rmq\Models\Message::findFirst([['_id' => $id]]);

        if ($message) {
            $points = ($message->progress + $points);
            $message->save(['progress' => $points < 100 ? $points : 100]);
        }
    }

    /**
     * @param $data
     * @return string
     */
    public function pushMessage($data)
    {

        if (isset($data['update_parent_progress'])) {
            $this->updateProgress($data['parent_id'], $data['update_parent_progress']);
            return false;
        }

        if (!isset($data['handle_added']) && $this->getDi()->getConfig()->get('app_code')) {
            $data['queue_name'] = $this->getDi()->getConfig()->get('app_code') . '_' . $data['queue_name'];
            $data['handle_added'] = 1;
        }
        // Create a service builder using a configuration file
        $delay = 1;
        if (isset($data['delay'])) {
            $delay = $data['delay'];
        } elseif (isset($data['run_after']) && $data['run_after'] > time()) {
            $delay = $data['run_after'] - time();
        }

        $client = $this->getClient();
        $queueUrl = $this->getQueueUrl($data['queue_name']);
        if (!$queueUrl) {
            $result = $client->createQueue(array(
                'QueueName' => $data['queue_name'],
                'Attributes' => array(
                    'MaximumMessageSize' => 32 * 4096, // 4 KB
                    'VisibilityTimeout' => 5 * 60, // 2 min max time to process the message
                ),
            ));
            $queueUrl = $result->get('QueueUrl');
        }
        $client->sendMessage(array(
            'QueueUrl' => $queueUrl,
            'MessageBody' => json_encode($data),
            'DelaySeconds' => $delay,
        ));

    }

    public function getQueueUrl($queueNameOrPrefix)
    {
        $queueUrl = false;
        try {
            $result = $this->getClient()->getQueueUrl([
                'QueueName' => $queueNameOrPrefix,
            ]);
            $queueUrl = $result->get('QueueUrl');
        } catch (AwsException $e) {
            //    var_dump($e->getMessage());die;
        }
        return $queueUrl;
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->client = new SqsClient(include BP . '/app/etc/aws.php');
        }
        return $this->client;
    }
    public function getS3Client()
    {
        return new S3Client(include BP . '/app/etc/aws.php');
    }

    public function getDataFrom($filePath)
    {
        return require $filePath;
    }

    // handle the case of mysql server gone away to reset the mysql connection
    public function loadDatabase()
    {
        foreach ($this->di->getConfig()->databases as $key => $database) {
            if ($database['adapter'] == 'Mongo') {

            } else {
                if ($connection = $this->di->get($key)) {
                    $result = $connection->close();
                    $this->di->getLog()->logContent('db connection is active:' . ($result ? "1" : "0"), \Phalcon\Logger::CRITICAL);
                }
                $this->di->set(
                    $key,
                    function () use ($database) {
                        return new DbAdapter((array) $database);
                    }
                );
            }
        }
    }

    public function testQueue()
    {
        while (true) {
            $this->di->getLog()->logContent('working  ', 'info', 'testing_queue.log');
            $arr = [];
            print_r("null");
            sleep(10);
        }
    }
    public function consume($queueName, $durable = false)
    {
        $this->di->get('\App\Core\Components\AppCode');
        if ($queueName == 'facebook_testing_queue') {
            $this->testQueue();
            return;
        }
        $client = $this->getClient();
        if ($queueUrl = $this->getQueueUrl($queueName)) {
            $result = $client->receiveMessage(array(
                'QueueUrl' => $queueUrl,
                'MaxNumberOfMessages' => 10,
            ));
            do {
                try {
                    while ($messages = $result->get('Messages')) {
                        foreach ($messages as $message) {
                            $messageArray = json_decode($message['Body'], true);
                            if (isset($messageArray['S3Payload'])) {
                                $s3 = $this->getS3Client();
                                $result = $s3->getObject([
                                    'Bucket' => $this->di->getConfig()->get('sqs_s3_bucket'),
                                    'Key' => $messageArray['S3Payload']['Key'],
                                ]);
                                $messageArray = json_decode($result['Body'], true);
                            }
                            if (isset($messageArray['user_id'])) {
                                $user = \App\Core\Models\User::findFirst([['_id' => $messageArray['user_id']]]);
                                
                                if ( isset($messageArray['appCode']) ) {
                                    $this->di->getAppCode()->set($messageArray['appCode']);
                                } else if ( isset($messageArray['data']['appCode']) ) {
                                    $this->di->getAppCode()->set($messageArray['data']['appCode']);
                                }
                                if ( isset($messageArray['appTag']) ) {
                                    $this->di->getAppCode()->setAppTag($messageArray['appTag']);
                                } else if ( isset($messageArray['data']['appTag']) ) {
                                    $this->di->getAppCode()->setAppTag($messageArray['data']['appTag']);
                                }
                                if ($user) {
                                    $user->id = (string) $user->_id;
                                    $this->di->setUser($user);
                                    $decodedToken = [
                                        'role' => 'admin',
                                        'user_id' => $messageArray['user_id'],
                                    ];
                                    
                                    $this->di->getRegistry()->setDecodedToken($decodedToken);                                    
                                    if (is_array($messageArray['user_id'])) {
                                        $this->client->deleteMessage(array(
                                            'QueueUrl' => $queueUrl,
                                            'ReceiptHandle' => $message['ReceiptHandle'],
                                        ));
                                        continue;
                                    }

                                    if (strcmp($this->di->getUser()->id, $messageArray['user_id']) !== 0) {
                                        $this->di->getLog()->logContent('Userid from Di container = ' . json_encode($this->di->getUser()->id) . " Userid in sqs queue = " . $messageArray['user_id'], 'info', 'shopify' . DS . 'error_sqs.log');
                                    }
                                } else {
                                    // die(" day9");
                                    $this->di->getLog()->logContent('4. inside foreach | data : ' . print_r($message['Body'], true), 'info', 'sqs_user_not_found.log');
                                    $this->client->deleteMessage(array(
                                        'QueueUrl' => $queueUrl,
                                        'ReceiptHandle' => $message['ReceiptHandle'],
                                    ));
                                    continue;
                                }

                            }
                            $msgResponse = $this->processMsg($messageArray);
                            print_r($msgResponse);
                            if (!$msgResponse) {
                                $this->getQueueUrl($this->getDi()->getConfig()->get('app_code') . '_failed');
                            } else {

                                if ($msgResponse === 2) {
                                    if (!isset($messageArray['retry']) || $messageArray['retry'] <= 5) {
                                        $messageArray['delay'] = /* isset($messageArray['delay']) ? ($messageArray['delay']*3*60):*/5;
                                        $messageArray['retry'] = isset($messageArray['retry']) ? $messageArray['retry'] + 1 : 1;
                                        $this->pushMessage($messageArray);
                                    }

                                }
                            }
                            $this->client->deleteMessage(array(
                                'QueueUrl' => $queueUrl,
                                'ReceiptHandle' => $message['ReceiptHandle'],
                            ));
                        }
                        $result = $client->receiveMessage(array(
                            'QueueUrl' => $queueUrl,
                            'MaxNumberOfMessages' => 10,
                        ));
                    }
                    sleep(10);
                    $result = $client->receiveMessage(array(
                        'QueueUrl' => $queueUrl,
                        'MaxNumberOfMessages' => 10,
                    ));
                } catch (\Exception $e) {
                    if (strpos($e->getMessage(), '2006 MySQL server has gone away') !== false) {
                        $this->di->getLog()->logContent('revive working ', \Phalcon\Logger::CRITICAL);
                        throw new \Exception('revive');
                    }
                    
                    $messageCode =  preg_replace('/[^A-Za-z0-9\-]/', '', $e->getMessage());
                    $this->di->getLog()->logContent($e->getMessage().PHP_EOL.$e->getTraceAsString().PHP_EOL,\Phalcon\Logger::EMERGENCY, "cli.log",false,$messageCode);
                    throw new \Exception('revive');

                    /*
                    $result = $client->receiveMessage(array(
                        'QueueUrl' => $queueUrl,
                        'MaxNumberOfMessages' => 10,
                    ));
                    */
                }
            } while ($durable);
            die('done');
        } else {
            die('queue not found');
        }
    }

    public function pushFutureMessageHandlerToQueue()
    {
        $handlerData = [];
        $handlerData['type'] = 'full_class';
        $handlerData['class_name'] = 'App\Core\Components\Message\Handler\MongoAndRmq';
        $handlerData['method'] = 'queueFutureMessagesHandler';
        $handlerData['queue_name'] = 'future_messages';
        $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
        $helper->pushMessage($handlerData);
    }

    public function queueFutureMessagesHandler()
    {
        $result = $this->queueFutureMessages();
        $this->pushFutureMessageHandlerToQueue();
        return $result;
    }

    public function queueFutureMessages()
    {
        $model = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $model->setSource('queue_messages');
        $aggregation = [];
        $aggregation[] = ['$match' => ['run_after' => ['$lte' => new \MongoDB\BSON\UTCDateTime(time())]]];
        $aggregation[] = [
            '$replaceRoot' => ['newRoot' => ['$mergeObjects' => [['$arrayElemAt' => ['$fromItems', 0]], '$$ROOT']]],
        ];
        $messages = $model->getCollection()->aggregate($aggregation,
            ["typeMap" => ['root' => 'array', 'document' => 'array']]
        );

        foreach ($messages->toArray() as $message) {
            $handlerData = [];
            $handlerData['type'] = 'full_class';
            $handlerData['class_name'] = 'App\Core\Components\Message\Handler\MongoAndRmq';
            $handlerData['method'] = 'getMessageDataByReferenceId';
            $handlerData['queue_name'] = $message['message_data']['queue_name'];
            $handlerData['message_reference_id'] = $message['_id'];
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            try {
                if (!$helper->pushMessage($handlerData)) {
                    $model->getCollection()->deleteOne(['_id' => $message['_id']]);
                    return false;
                } else {
                    $model->getCollection()->updateOne(['_id' => $message['_id']], ['$set' => ['status' => 'pending']]);
                }
            } catch (\Exception $e) {
                $model->getCollection()->deleteOne(['_id' => $message['_id']]);
                return false;
            }
        }
        return true;
    }

    /**
     * @param $referenceId
     * @return mixed
     */
    public function getMessageDataByReferenceId($referenceId)
    {
        $model = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $model->setSource('queue_messages');
        $model->getCollection()->findOneAndUpdate(
            ['_id' => $referenceId],
            ['$set' => ['status' => 'processing']],
            ['writeConcern' => new \MongoDB\Driver\WriteConcern('majority')]
        );
        $model = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $model->setSource('queue_messages');
        $message = $model->getCollection()->findOne(['_id' => $referenceId], ["typeMap" => ['root' => 'array', 'document' => 'array']]);
        if ($message && isset($message['message_data'])) {
            return $message['message_data'];
        } else {
            return false;
        }
    }

    public function processMsg($msgArray)
    {
        $logger = $this->di->getLog();
        $consumerTag = isset($msgArray['consumer_tag']) ? $msgArray['consumer_tag'] : 'no-tag';

        $msgArray['type'] = $msgArray['type'] ?? 'default';
        $queueName = $msgArray['queue_name'];
        if ($msgArray['type'] == 'url') {
            return false;
        } elseif ($msgArray['type'] == 'full_class') {
            if (isset($msgArray['class_name'])) {
                $obj = $this->getDi()->getObjectManager()->get($msgArray['class_name']);
                $method = $msgArray['method'];
                $start_time = microtime(true);
                $logger->logContent("\tcalled handler method {$msgArray['class_name']} -> {$method}:" . $start_time . PHP_EOL, \Phalcon\Logger::DEBUG, "queue-processing/{$queueName}.log");
                $response = $obj->$method($msgArray);
                $end_time = microtime(true);
                $execution_time = ($end_time - $start_time);
                $logger->logContent("\tHandler method {$msgArray['class_name']} -> {$method} executed in  :" . $execution_time . PHP_EOL, \Phalcon\Logger::DEBUG, "queue-processing/{$queueName}.log");

                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');

                /* tdo if class not found */
                return true;
            }
        } elseif ($msgArray['type'] == 'class') {
            if (isset($msgArray['class_name'])) {
                $obj = $this->getDi()->getObjectManager()->get('App\\Rmq\\Handlers\\' . $msgArray['class_name']);

                $method = $msgArray['method'];
                $response = $obj->$method($msgArray);
                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');
                /* tdo if class not found */
                return true;
            }
        } else {
            $obj = $this->getDi()->getObjectManager()->get('App\\Rmq\\Handlers\\Default1');
            if (isset($msgArray['method'])) {
                $method = $msgArray['method'];
                echo $method;
                $response = $this->$method($msgArray);
                print_r($response);die();
                return $response;
            } else {
                $logger->logContent(print_r($msgArray, true), \Phalcon\Logger::DEBUG, 'queue_failed.log');
                return true;
                /*todo handle without method message*/
            }
        }
    }
}
