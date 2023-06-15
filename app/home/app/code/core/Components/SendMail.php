<?php
namespace App\Core\Components;

use \Firebase\JWT\JWT;
use Magento\Setup\Exception;
use Phalcon\Mvc\View\Engine\Volt\Compiler as VoltCompiler;

class SendMail extends Base
{
    public function send($data)
    {
        $compiler = new VoltCompiler;
        //$this->di->getLog()->logContent('Mail Data '.json_encode($data) ,\Phalcon\Logger::CRITICAL,'mail.log');
       // Compile a template in a file specifying the destination file
        $path = BP.DS.'var'.DS.'compile'.DS.'email'.DS;
        if (!file_exists($path)) {
            $oldmask = umask(0);
            mkdir($path, 0777, true);
            umask($oldmask);
        }
        $data['banner'] = $this->di->getConfig()->backend_base_url . 'media/680x300.png';
        $compiler->setOption('compiledPath', $path);
        $compiler->setOption('compiledSeparator', '-');
        $template = $this->findTemplate($data['path']);
        $compiler->compile($template);
        
        $template = $compiler->getCompiledTemplatePath();
        extract($data);
        ob_start();
        require $template;
        $content = ob_get_clean();
        $email = $data['email'];
        if ($this->di->getConfig()->enable_rabbitmq && $this->di->getConfig()->mail_through_rabbitmq) {
            $handlerData = [
                'type' => 'class',
                'class_name' => 'Qhandler',
                'method' => 'sendMail',
                'queue_name' => 'general',
                'data' => [
                    'email' => $email,
                    'subject' => $data['subject']??'',
                    'content' => base64_encode($content),
                    
                ],
                'bearer' => $this->di->getConfig()->get('rabbitmq_token')
            ];
            if (isset($data['bccs'])) {
                $handlerData['data']['bccs'] = $data['bccs'];
            }
            if ($this->di->getConfig()->enable_rabbitmq_internal) {
                $this->di->getLog()->logContent('Rabbitmq Internal Mail adding data '.json_encode($handlerData), \Phalcon\Logger::CRITICAL, 'mail.log');
                $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
                $responseData = ['feed_id'=>$helper->createQueue($handlerData['queue_name'], $handlerData),'success'=>true];
            } else {
                $this->di->getLog()->logContent('Rabbitmq External Mail', \Phalcon\Logger::CRITICAL, 'mail.log');
                $request = $this->di->get('\App\Core\Components\Helper')->curlRequest($this->di->getConfig()->rabbitmq_url . '/rmq/queue/create', $handlerData, false);
            }
        } else {
            $this->di->getLog()->logContent('Senging mail directly', \Phalcon\Logger::CRITICAL, 'mail.log');
            $mailer = $this->di->getObjectManager()->get('mailer');
            if (isset($data['bccs'])) {
                return $mailer->sendmail($email, $data['subject']??'', $content, 0, true, $data['bccs']);
            } else {
                return $mailer->sendmail($email, $data['subject']??'', $content, 1);

            }
            
        }
    }

    /**
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function findTemplate($path){
        $findInPaths = [
             BP . DS . 'app'.DS.'design'.DS,
             BP . DS . 'app'.DS.'code'.DS
        ];
        if (file_exists($path)) {
            return $path;
        }
        foreach($findInPaths as $basePath) {
            if(file_exists($basePath.$path)) {
                return $basePath.$path;
            }
        }
        throw new \Exception('Template not found');
    }
}
