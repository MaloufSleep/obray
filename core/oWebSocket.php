<?php
	/*****************************************************************************

	The MIT License (MIT)

	Copyright (c) 2013 Nathan A Obray <nathanobray@gmail.com>

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the 'Software'), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

	*****************************************************************************/

	if (!class_exists( 'OObject' )) { die(); }

	class oClient {
		
	}

	/********************************************************************************************************************

		OOBJECT:

	********************************************************************************************************************/

	Class oWebSocket extends ODBO {

		public function __construct($params){

			$this->host = !empty($params["host"])?$params["host"]:"localhost";
			$this->port = !empty($params["port"])?$params["port"]:"80";

			$this->console("Binding to ".$this->host.":".$this->port."\n");
			//Create TCP/IP sream socket
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			//reuseable port
			socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);

			//bind socket to specified host
			socket_bind($this->socket, $this->host, $this->port);

			//listen to port
			socket_listen($this->socket);

			//create & add listning socket to the list
			$this->clients = array($this->socket);
			$this->clientMap = array();
			$this->clientData = array();


			while (true) {
				//manage multipal connections
				$changed = $this->clients;
				//returns the socket resources in $changed array
				socket_select($changed, $null, $null, 0, 10);
				
				//check for new socket
				if (in_array($this->socket, $changed)) {
					$socket_new = socket_accept($this->socket); //accpet new socket

					$this->clients[] = $socket_new; //add socket to client array
						
					$header = socket_read($socket_new, 1024); //read data sent by the socket
					$ouser = $this->handshake($header, $socket_new, $this->host, $this->port); //perform websocket handshake
					$ouser->subscriptions = array( "all" => TRUE );
					$this->clientData[ count($this->clients) - 1 ] = $ouser;

					socket_getpeername($socket_new, $ip); //get ip address of connected socket
					$response = (object)array( 'channel'=>'all', 'type'=>'broadcast', 'message'=>$ouser->ouser_first_name.' '.$ouser->ouser_last_name.' connected.' ); //prepare json data
					$this->send($response); //notify all users about new connection
					
					//make room for new socket
					$found_socket = array_search($this->socket, $changed);
					unset($changed[$found_socket]);
					print_r( count($this->clients) );
					echo "\n";
				}
				
				//loop through all connected sockets
				foreach ( array_keys($changed) as $changed_key) {	
					$changed_socket = $changed[$changed_key];
					//check for any incomming data
					while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
					{
						$msg = $this->unmask($buf); //unmask data
						//prepare data to be sent to client
						$msg = json_decode($msg);
						
						if( !empty($msg) ){

							switch( $msg->type ){
								case 'subscription':
									$this->clientData[$changed_key]->subscriptions[ $msg->channel ] = TRUE;
									break;
								case 'broadcast': case 'navigate':
									$this->send($msg);
									break;
							}
						}
						
						break 2; //exist this loop
					}
					
					$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
					if ($buf === false) { // check disconnected client
						// remove client for $clients array
						$found_socket = array_search($changed_socket, $this->clients);
						$this->console("%s","Attempting to disconnect index: ".$found_socket."\n");
						socket_getpeername($changed_socket, $ip);
						if( !empty($this->clientData[$found_socket]) ){
							$ouser = $this->clientData[$found_socket];	
							//notify all users about disconnected connection
							$response = (object)array( 'channel'=>'all', 'type'=>'broadcast', 'message'=>$ouser->ouser_first_name.' '.$ouser->ouser_last_name.' disconnected.');
							$this->send($response);
						}
						unset($this->clients[$found_socket]);
					}
				}
			}

		}

		function send($msg){			
			foreach( array_keys($this->clients) as $changed_key){
				if( !empty($this->clientData[$changed_key]) ){
					if( !empty($this->clientData[$changed_key]->subscriptions[$msg->channel]) ){
						print_r($msg);
						$message =  $this->mask( json_encode($msg) );
						socket_write($this->clients[$changed_key], $message, strlen($message));	
					}
				}
			}
			return true;
		}

		//handshake new client.
		function handshake($header,$client_conn, $host, $port){
			$headers = array();
			$lines = preg_split("/\r\n/", $header);
			$ouser = new stdClass();
			foreach($lines as $line)
			{
				$get_request = explode("?",$line);
				if( count($get_request) > 1 ){ 
					$get = $get_request; 
					$get = explode(" ",$get[1]);
					$ouser = explode("=",$get[0]);
					if( count($ouser) === 2 ){
						$ouser = $this->route('/obray/OUsers/get/?ouser_id='.$ouser[1])->getFirst();
					}
				}
				$line = chop($line);

				if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
				{
					$headers[$matches[1]] = $matches[2];
				}
			}
			

			$secKey = $headers['Sec-WebSocket-Key'];
			$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
			//hand shaking header
			$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: $host\r\n" .
			"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
			socket_write($client_conn,$upgrade,strlen($upgrade));
			return $ouser;
		}

		//Unmask incoming framed message
		private function unmask($text) {
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
		private function mask($text)
		{
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


	}
?>