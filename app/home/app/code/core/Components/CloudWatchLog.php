<?php

namespace App\Core\Components;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Formatter\JsonFormatter;

class CloudWatchLog extends Base
{
    public function logContent($content, $type = Logger::DEBUG,  $streamName = 'system.log', $groupName = 'apiconnect',$retentionDays = 14)
    {
       // Instantiate AWS SDK CloudWatch Logs Client
        $client = CloudWatchLogsClient::factory(include BP.'/app/etc/cloudwatch.php');

// Instantiate handler (tags are optional)
        $handler = new CloudWatch($client, $groupName, $streamName, $retentionDays, 10000, []);
// Optionally set the JsonFormatter to be able to access your log messages in a structured way
        $handler->setFormatter(new JsonFormatter());
// Create a log channel
        $log = new Logger('Remote Log');

// Set handler
        $log->pushHandler($handler);

// Add records to the log

        switch ($type) {
            case Logger::CRITICAL:
                $log->critical($content);
                break;
            case Logger::EMERGENCY:
                $log->emergency($content);
                break;
            case Logger::DEBUG:
                $log->debug($content);
                break;
            case Logger::ERROR:
                $log->error($content);
                break;
            case Logger::INFO:
                $log->info($content);
                break;
            case Logger::NOTICE:
                $log->notice($content);
                break;
            case Logger::WARNING:
                $log->warning($content);
                break;
            case Logger::ALERT:
                $log->alert($content);
                break;
            default:
                $log->info($content);
                break;
        }
    }

}
