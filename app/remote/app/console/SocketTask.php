<?php

use Phalcon\Cli\Task;
use App\Core\Models\Resource;
use App\Core\Models\Acl\Role;

class SocketTask extends Task
{
	public $host = 'localhost';
	public $port = '10000';
	public $socket = '';
	public $clients = [];
	public $data = ['user_type'=>'merchant','id'=>false];
	public $userData = [];
	public $debug = true;
	public $isEcho = true;

	/**
	 * $params $data = ['host'=>'localhost','port'=>10000]
	 **/
	public function init($data = []){
		foreach($data as $key => $value){
			$this->$key = $value;
		}
	}

	public function log($msg,$break=PHP_EOL){
		if($this->debug){
			echo $msg.$break;
		}
	}

	public function getHashData($hash)
	{
		$directory = \Yii::getAlias('@webroot').$this->_hashFilePath;
		if(file_exists($directory.'/'.$hash)){
			return json_decode(file_get_contents($directory.'/'.$hash),true);
		}
		return false;
	}
	public function sslconnectAction() {
		$context = stream_context_create();

		// local_cert must be in PEM format
		stream_context_set_option($context, 'ssl', 'local_cert', '/var/www/engine-cert-keys/fullchain2.pem');
		stream_context_set_option($context, 'ssl', 'local_pk', '/var/www/engine-cert-keys/privkey2.pem');
		stream_context_set_option($context, 'ssl', 'allow_self_signed', false);
		stream_context_set_option($context, 'ssl', 'verify_peer', false);
		// Create the server socket
		$this->socket = stream_socket_server('ssl://0.0.0.0:10000', $errno, $errstr, STREAM_SERVER_BIND|STREAM_SERVER_LISTEN, $context);

		//create & add listning socket to the list
		$this->clients = array($this->getResourceId($this->socket)=>$this->socket);
		$null = NULL;
		$errorCame = false;
		//start endless loop, so that our script doesn't stop
		while (true) {
			// manage multipal connections
			$changed = $this->clients;
			$null = NULL;
			//returns the socket resources in $changed array
			stream_select($changed, $null, $null, $null, $null);
			//check for new socket
			if (in_array($this->socket, $changed)) {
				$this->logger('Trying to accept socket. Socket => ' . $this->socket);
				ob_start();
				$socket_new = stream_socket_accept($this->socket); // accpet new socket
				$ob = ob_get_clean();
				$this->logger('Got new socket => ' . $socket_new);
				if (strpos($ob, 'PHP Warning:') != false) {
					$this->logger('PHP warning came, so restarting the socket.');
					$errorCame = true;
					break;
				}
				if ($socket_new) {
					$this->logger('Accept new socket');
					$this->clients[$this->getResourceId($socket_new)] = $socket_new; //add socket to client array
					var_dump($this->getResourceId($socket_new));
					$this->logger('Reading new socket');
					$header = fread($socket_new, 1024); //read data sent by the socket
					$this->performHandshaking($header, $socket_new, $this->host, $this->port); //perform websocket handshake
					$ip = stream_socket_get_name($socket_new, true); //get ip address of connected socket
					// make room for new socket
					$found_socket = array_search($this->socket, $changed);
					unset($changed[$found_socket]);
				}
			}
			
			// loop through all connected sockets
			foreach ($changed as $changed_socket) {
				if (gettype($changed_socket) != 'boolean' && feof($changed_socket) !== true) {
					while($buf = fread($changed_socket,1024))
					{
						$this->logger('Updated Resource :'.$this->getResourceId($changed_socket));
						$resourceId = $this->getResourceId($changed_socket);
						$this->logger('Resource ID => ' . $resourceId);
						$this->logger('recieveMessageFromClient');
						if($bufferMsg=json_decode($buf,true))
						{
							if($data = $this->getHashData($bufferMsg['hash']))
							{
								if(!$isClientOnline )
								{
									
								}
							}
							else
							{
								$this->logger('Hash Not Found');
							}
						}
						else
						{
							$received_text = $this->unMask($buf); //unmask data
							$decoded = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($received_text);
							$userId = '';
			                if ($decoded['success'] == true) {
			                	$userId = $decoded['data']['user_id'];
			                	$this->userData[$userId][$resourceId] = '';
						        $allUserNotifications = $this->getUserNotificationsAndQueuedTasks($userId);
			                } else {
			                	$allUserNotifications = $this->getUserNotificationsAndQueuedTasks(-1);
			                }
			                $this->logger('Token decode response => ' . json_encode($decoded));
			                $this->logger('User Id found => ' . $userId);

							$msg = $this->mask(json_encode($allUserNotifications));
							if (!isset($resourceId)) {
								$resourceId = $received_text;
							}
							$recieverSocket = $this->clients[$resourceId];
							$this->logger('Userdata Array => ');
							$this->logger(json_encode($this->userData, true));
							fwrite($recieverSocket,$msg);
							if(isset($tst_msg['hash']))
							{
								$this->logger(json_encode($tst_msg, true));
								if($data = $this->getHashData($tst_msg['hash']))
								{
									if(!isset($this->liveMembers[$data['sender_ref']])) {
										$this->liveMembers[$data['sender_ref']] = $data;
									}
									$this->resourcesMap[$resourceId] = $data;
									
									$msgData = array('type'=>'action', 'message'=>'Message');

									$msg = $this->mask(json_encode($msgData));
									$recieverSocket = $this->clients[$resourceId];
									fwrite($recieverSocket,$msg);
									
								}
								else
								{
									$this->logger('Hash Not Found');
								}
								$this->logger('recieved Hash:'.$tst_msg['hash']);
							}
							else
							{
								
							}
						}
						
						break 2; //exist this loop
					}
					
					$buf = fread($changed_socket, 1024);
					if ($buf === false) {
						$this->logger('Calling clientDisconnected function');
						$this->clientDisconnected($this->getResourceId($changed_socket));
					}	
				}
			}
		      
		}
		// close the listening socket
		fclose($this->socket);
		if ($errorCame) {
			$this->sslconnectAction();
		}
	}

    public function clientDisconnected($resourceId)
	{
		$this->logger('Resource ' . $resourceId . ' Not Found');
		foreach ($this->userData as $key => $value) {
			if (isset($value[$resourceId])) {
				unset($this->userData[$key][$resourceId]);
			}
		}
		$this->logger('Userdata => ' . json_encode($this->userData, true));
	}

	public function getResourceId($resource)
	{
		$idString = (string)$resource;
		return str_replace('Resource id #', '', $idString);
	}

	//Unmask incoming framed message
	public function unMask($text) {
		$length = ord($text[1]) & 127;
		if($length == 126) {
			$masks = substr($text, 4, 4);
			$data = substr($text, 8);
		}
		elseif($length == 127) {
			$masks = substr($text, 10, 4);
			$data = substr($text, 14);
		}
		else {
			$masks = substr($text, 2, 4);
			$data = substr($text, 6);
		}
		$text = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i%4];
		}
		return $text;
	}

	//Encode message for transfer to client.
	public function mask($text) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($text);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$text;
	}

	public function broadcast($msg) {
		$msg = $this->mask(json_encode($msg));
		foreach($this->clients as $client){
			fwrite($client,$msg);
		}
						
	}

	public function sendMessageToSpecificClient($clientId) {
		$this->logger('Came in sendMessageToSpecificClient');
		$userNotifications = $this->getUserNotificationsAndQueuedTasks($clientId);
		$this->logger('Got user notification data for userID => ' . $clientId);
		$msg = $this->mask(json_encode($userNotifications));
		if (isset($this->userData[$clientId])) {
			$this->logger('Client id is set in userData');
			$resourceIds = $this->userData[$clientId];
			$this->logger('Resource IDS => ' . json_encode($resourceIds));
			foreach ($resourceIds as $key => $value) {
				$this->logger('Getting socket name for => ' . $key);
				$clientActive = stream_socket_get_name($this->clients[$key], true);
				$this->logger('Got socket name for => ' . $key);
				if ($clientActive) {
					$this->logger('Client active. User ID => ' . $clientId);
					$client = $this->clients[$key];
					fwrite($client,$msg);
					$this->logger('Message send successfully');
				} else {
					$this->logger('Client not active. User ID => ' . $clientId);
					unset($this->userData[$clientId][$key]);
					if (!count($this->userData[$clientId])) {
						unset($this->userData[$clientId]);
					}
				}
			}
		} else {
			$this->logger('Client id not set in userData');
		}
	}
	// handshake new client.
	public function performHandshaking($receved_header,$client_conn, $host, $port)
	{
		$headers = array();
		$lines = preg_split("/\r\n/", $receved_header);
		foreach($lines as $line)
		{
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
			{
				$headers[$matches[1]] = $matches[2];
			}
		}
		
		if(isset($headers['userId'])){
			$this->sendMessageToSpecificClient($headers['userId']);
		}
		if(isset($headers['Sec-WebSocket-Key']))
		{
			$secKey = $headers['Sec-WebSocket-Key'];
			$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			//hand shaking header
			$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: {$this->host}\r\n" .
			"WebSocket-Location: ws://{$this->host}:{$this->port}/demo/shout.php\r\n".
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
			fwrite($client_conn,$upgrade);
		}
	}

	public function getUserNotificationsAndQueuedTasks($userId) {
		$allUserNotifications = $this->di->getObjectManager()->create('\App\Core\Models\Notifications')::find(
			            [
			                "user_id='{$userId}' AND seen=false",
			                'order' => 'created_at DESC',
			                'limit' => 100
			            ]
			        );

		$queuedTasks = $this->di->getObjectManager()->create('\App\Core\Models\QueuedTask')::find(["user_id='{$userId}'", "limit" => 100]);
		$allQueuedTask = [];
        if (count($queuedTasks)) {
            foreach ($queuedTasks as $feedKey => $feedValue) {
                $feedId = $feedValue->feed_id;
                if($feedId) {
                    $message = $this->di->getObjectManager()->create('\App\Rmq\Models\Message')::findFirst($feedId);
                    $progress = $message->progress == '' ? 0 : $message->progress;
                    if ($progress < 100) {
                        $allQueuedTask[] = [
                            'status' => $progress . '%',
                            'text' => $feedValue->feed_message,
                            'id' => $feedValue->id
                        ];   
                    }
                }   
            }
        }
        return [
        	'notifications' => $allUserNotifications,
        	'queuedTasks' => $allQueuedTask
        ];
	}

	public function logger($message) {
		if ($this->isEcho) {
			echo $message . PHP_EOL;
		} else {
			$this->di->getLog()->logContent($message,\Phalcon\Logger::CRITICAL,'websocket_log.log');
		}
	}
}
?>