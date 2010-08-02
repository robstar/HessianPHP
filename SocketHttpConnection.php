<?php


/**
 * This class stablishes communication to a remote Http URL using sockets and raw
 * data transmission
 * @package HessianPHP.Http
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_SocketHttpConnection extends Hessian_HttpConnection{
	var $__socket;
	
	/**
	 * Sends an HTTP request using the POST method
	 *  
	 * @param string data Content to be sent
	 * @return string Response from remote server
	 **/
	function POST($data){
		parent::POST($data);
		$this->error = false;
		$this->headers['Content-length'] = strlen($data);
		$path = $this->urlInfo['path'];
		// NOTE: the entire URL is required for proxy connections
		$out = "POST $this->url HTTP/1.1\r\n";
		foreach($this->headers as $header => $value){
			$out .= "$header: $value\r\n";
		}
		$out .= "\r\n".$data;
		if($this->open()){
			$this->write($out);
			$response = $this->read();
			return $response;
		}
		return false;
	}

	/**
	 * Opens a socket connection to a remote host
	 *  
	 * @param string host Remote host
	 * @param int port Remote port
	 **/
	function open(){
		$timeout = 5;
		if(isset($this->options['timeout']) && is_int($this->options['timeout'])){
			$timeout = $this->options['timeout'];
		}
		if(isset($this->options['proxy_host']) && isset($this->options['proxy_port'])) {
			$this->__socket = @fsockopen($this->options['proxy_host'], $this->options['proxy_port'], $errno, $errstr, $timeout);
		} else {
			$this->__socket = @fsockopen($this->urlInfo['host'], $this->urlInfo['port'], $errno, $errstr, $timeout);
		}

		if (!$this->__socket) {
			$this->error = &new Hessian_HttpError("HttpError: Error opening socket communication: $errstr ($errno)");
			return false;
		}
		return true;
	}

	/** @access protected */
	function close(){
		fclose($this->__socket);
	}
	
	/** @access protected */
	function write($data){
		fwrite($this->__socket,$data);
	}

	/**
	 * Parses incoming header information and checks for repeated headers
	 *  
	 * @param string head first line of HTTP headers
	 * @access protected 
	 **/
	function parseHeaders($head=''){
		if($head == '')
			$head = trim(fgets($this->__socket, 4096));
		
		//parse header
		if(preg_match("/HTTP\/(1.[01]) ([\d]{3})[ ]*(.*)/i",$head,$parts)){
			$this->httpVersion = $parts[1];
			$this->code = $parts[2];
			$this->message = $parts[3];
		} else {
			$this->error = &new Hessian_HttpError("HttpError: Malformed HTTP header",0,$this->headers);
			return false;
		}
		$this->responseHeaders[] = trim($head);
		while ($str = trim(fgets($this->__socket, 4096))) {
			$this->responseHeaders[] = trim($str);
			if(preg_match("/Content-length:[ ]+([\d]+)/i",$str,$headParts)){
				$this->length = $headParts[1];			
			}
		}

		// check for HTTP 100 Continue state and reparse headers, this happens in IIS with PHP5 as CGI
		switch($this->code){
			case '100': $this->parseHeaders();
		}
		return true;
	}

	/**
	 * Read the reply from the socket, parses incoming headers and returns the content
	 *  
	 * @return string body content of the response
	 **/
	function read(){
		if(!$this->parseHeaders()) return;
		$line = '';
		$body = '';

		while (!feof($this->__socket)){
			$line = fgets($this->__socket, 32768);
			$body .= $line;
		}
		$this->body = $body;
		
		// 
		if($this->code[0] == '3'){
			$this->error = &new Hessian_HttpError("HttpError: Redirection is not supported: $this->message,$this->code",0,$this->headers,$this->body);
			return false;
		}

		if($this->code > 400){
			$this->error = &new Hessian_HttpError("HttpError: $this->message,$this->code",0,$this->headers,$this->body);
			return false;
		}
		return $body;
	}
}