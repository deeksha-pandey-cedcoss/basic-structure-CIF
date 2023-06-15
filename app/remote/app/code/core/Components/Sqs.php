<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @category    Ced
 * @package     Ced_Shopifynxtgen
 * @author      CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright   Copyright CEDCOMMERCE (http://cedcommerce.com/)
 * @license     http://cedcommerce.com/license-agreement.txt
 */

namespace App\Core\Components;

use Exception;
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;


class Sqs extends Base
{



    public function createQueue($queueName,$sqsClient)
    {
        try {
            $queueUrl = $this->getQueueUrl($queueName, $sqsClient);
            if($queueUrl === false) {
                $result = $sqsClient->createQueue([
                    'QueueName'  => $queueName,
                    'Attributes' => [
                        'DelaySeconds'       => 0,
                        'MaximumMessageSize' => 200000, // 4 KB
                        'VisibilityTimeout' => 2 * 60, // 2 min max time to process the message
                    ]
                ]);

                $queueUrl = $result->get('QueueUrl');
            }

            if($queueUrl)
            {
                return [
                    'success'   => true,
                    'queue_url'  => $queueUrl
                ];
            }
            else
            {
                return [
                   'success'    => false,
                   'msg'        => 'something went wrong in sqs'
                ];
            }
        } catch (AwsException $e){
            return [
               'success'    => false,
               'msg'        => 'Sqs credentials are not valid',
               'error'      => $e->getAwsErrorMessage()
            ];
        } catch (Exception $e){
            return [
                'success'   => false,
                'msg'       => 'Sqs credentials are not valid',
                'error'     => $e->getMessage()
            ];
        }
        
    }

    public function getQueueUrl($queueName,$client)
    {

        $result = $client->listQueues(array(
            'QueueNamePrefix' => $queueName
        ));
        $queueUrl = false;
        if ($queues = $result->getPath('QueueUrls')) {
            $queueUrl = $queues[0];
        }
        return $queueUrl;
    }

    public function getClient($region,$key,$secret)
    {
        

        
        $config = [
            'version'     => 'latest',
            'region'      => $region,
            'credentials' => [
                'key'    => $key,
                'secret' => $secret
            ]
        ];

        $sqsClient = new SqsClient($config);
        
        return $sqsClient;
    }
}
