<?php


/**
 * Represents an error state from HTTP procotol communication
 * 
 * @package HessianPHP.Http
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 * @see HessianPHP.HttpCall
 **/
class Hessian_HttpError extends Exception{
	var $headers;
	var $body;
	var $message;
	var $code;
	var $time;

	function __construct($message='', $code=0, $headers=null,$body=null) {
		$this->message = $message;
		$this->code = $code;
		$this->headers = $headers;
		$this->body = $body;
		$this->time = date("Y-m-d H:i:s");
	}

	function getError(){
		return $this->error;
	}

	function getHeaders(){
		return $this->headers;
	}
	function getBody(){
		return $this->body;
	}

	function __toString(){
		if(phpversion() >= 5)
			return parent::__toString();
		$msg = "Message: ".$this->message."\n";
		$msg = "Code: ".$this->code."\n";
		$msg .= "Time: ".$this->time."\n";
		$msg .= "Headers: ".print_r($this->headers,true)."\n";
		$msg .= "Body: ".$this->body."\n";
		return $msg;
	}
}
