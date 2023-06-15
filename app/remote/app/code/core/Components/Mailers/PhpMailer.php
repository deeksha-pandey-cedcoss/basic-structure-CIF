<?php
namespace App\Core\Components\Mailers;

class PhpMailer extends \App\Core\Components\Base
{
    public function sendmail($email, $subject, $body, $debug = 0, $isHtml = true, $bccs = []){
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer();
            $mail->CharSet = "UTF-8";
            $mail->SMTPDebug = $debug;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host = $this->di->getConfig()->get('mailer')->get('smtp')->get('host');  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                               // Enable SMTP authentication
            $mail->Username = $this->di->getConfig()->get('mailer')->get('smtp')->get('username');;                // SMTP username
            $mail->Password = $this->di->getConfig()->get('mailer')->get('smtp')->get('password');                           // SMTP password
            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $this->di->getConfig()->get('mailer')->get('smtp')->get('port');                                    // TCP port to connect to

            //Recipients
            $mail->setFrom($this->di->getConfig()->get('mailer')->get('sender_email'), $this->di->getConfig()->get('mailer')->get('sender_name'));
            $mail->addAddress($email, $email);     // Add a recipient
            /*$mail->addAddress('ellen@example.com');               // Name is optional
            $mail->addReplyTo('info@example.com', 'Information');
            $mail->addCC('cc@example.com');*/
            if (!count($bccs)) {
                $bccs = explode(',', $this->di->getConfig()->get('mailer')->get('bcc'));   
            }
            foreach ($bccs as $key => $value) {
                $mail->addBCC($value);   
            }


            //Content
            $mail->isHTML($isHtml);                                  // Set email format to HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $body;

            return $mail->send();

        } catch (\Exception $e) {
            $this->di->getLog()->logContent('Message could not be sent. Mailer Error: '.$email.'=>'.$subject.' : '. $mail->ErrorInfo, \Phalcon\Logger::CRITICAL, 'mail.log');

        }
    }
}