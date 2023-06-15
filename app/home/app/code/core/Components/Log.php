<?php

namespace App\Core\Components;

use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream as FileAdapter;

class Log extends Base
{
    protected $log;
    protected $adapters = [];
    public function setDi(\Phalcon\Di\DiInterface $di):void
    {
        parent::setDi($di);
        $adapter = fopen(BP.DS.'var'.DS.'log'.DS.'system.log','a+');
        $this->adapters['system.log'] = $adapter;
        $this->di->set('log', $this);
    }

    public function createDirectory($dir)
    {
        $path = BP.DS.'var'.DS.'log'.DS.$dir;
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    public function logContent($content, $type = Logger::DEBUG, $file = 'system.log', $closeConnectionNow = false, $messageUniqueCode = false)
    {
        if($this->di->getConfig()->get('log_level') <= $type){
            if (isset($this->adapters[$file])) {
                $adapter = $this->adapters[$file];
            } else {
                $oldmask = umask(0);
                $this->createDirectory(dirname($file));
                $adapter = fopen(BP.DS.'var'.DS.'log'.DS.$file,'a+');
                umask($oldmask);
                if(!$closeConnectionNow)
                    $this->adapters[$file] = $adapter;

            }

            switch ($type) {
                case Logger::CRITICAL:
                    $this->log($adapter,Logger::CRITICAL,$content);

                    break;
                case Logger::EMERGENCY:

                    if( $messageUniqueCode ){
                        $currentTimestamp = time();
                        $email = '';
                        if($this->di->getConfig()->get('mailer'))
                            $email = $this->di->getConfig()->get('mailer')->get('critical_mail_reciever');
                     
                        if( $lastMessageTime = $this->di->getCache()->get('sendmail_'.$messageUniqueCode) )
                        {
                            if($lastMessageTime < $currentTimestamp-3600){
                                $this->di->getCache()->set('sendmail_'.$messageUniqueCode, $currentTimestamp);
                                $this->di->getMailer()->sendmail($email, "Critical issue",$content);
                            }
                            
                        }
                        else{
                            $this->di->getCache()->set('sendmail_'.$messageUniqueCode, $currentTimestamp);
                            $this->di->getMailer()->sendmail($email, "Critical issue",$content);
                        }
                    }
                    $this->log($adapter,Logger::CRITICAL,$content);
                    break;
                case Logger::DEBUG:
                    $this->log($adapter,Logger::DEBUG,$content);
                    break;
                case Logger::ERROR:
                    $this->log($adapter,Logger::ERROR,$content);
                    break;
                case Logger::INFO:
                    $this->log($adapter,Logger::INFO,$content);
                    break;
                case Logger::NOTICE:
                    $this->log($adapter,Logger::NOTICE,$content);
                    break;
                case Logger::WARNING:
                    $this->log($adapter,Logger::WARNING,$content);
                    break;
                case Logger::ALERT:
                    $this->log($adapter,Logger::ALERT,$content);
                    break;
            }

            if($closeConnectionNow){
                $this->close($adapter);
                return false;
            }else{
                return $adapter;
            }
        }

    }
    public function log($adapter,$loglevel,$content){
        fwrite($adapter,$content.PHP_EOL);
    }

    public function close($adapter){
        fclose($adapter);
    }
}
