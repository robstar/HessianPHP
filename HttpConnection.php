<?php


/**
 * Abstract class that represents an Http connection to a Url
 * @package HessianPHP.Http
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_HttpConnection{
	var $url;
	var $urlInfo;
	var $options;
	var $headers = array();
	var $responseHeaders = array();

	var $code;
	var $message;
	var $httpVersion;
	var $error = false;

	var $length = -1;


	function __construct($url,$options=false){
		$this->url = $url;
		$this->options = $options;
		$this->initUrl();
	}

    /**
    * validate url data passed to constructor
    *
    * @return boolean
    * @access private
    */
    function initUrl()
    {
		$this->urlInfo = parse_url($this->url);
        if (!is_array($this->urlInfo) ) {
			$this->error = &new Hessian_HttpError("Unable to parse URL $url");
			return FALSE;
        }
        if (!isset($this->urlInfo['host'])) {
			$this->error = &new Hessian_HttpError("No host in URL {$this->url}");
            return FALSE;
        }
        if (!isset($this->urlInfo['port'])) {
            
            if (strcasecmp($this->urlInfo['scheme'], 'HTTP') == 0)
                $this->urlInfo['port'] = 80;
            elseif (strcasecmp($this->urlInfo['scheme'], 'HTTPS') == 0) 
                $this->urlInfo['port'] = 443;
                
        }
		$this->headers['Host'] = $this->urlInfo['host'];
		$this->headers['Connection'] = 'close';
		
		if (isset($this->urlInfo['user'])) {
            $this->headers['Authorization'] = 'Basic ' . base64_encode($this->urlInfo['user'] . ':' . $this->urlInfo['pass']);
        }

		if(isset($this->options['proxy_user']) && isset($this->options['proxy_pass']))	
			$this->headers['Proxy-Authorization'] = 'Basic ' . base64_encode(
				$this->options['proxy_user'] .':'. $this->options['proxy_pass']);	

		// if there is an option for credentials, this takes precedence over url info
		if(isset($this->options['username']) && isset($this->options['password'])) {
			$this->headers['Authorization'] = 'Basic ' . base64_encode($this->options['username'].':'.$this->options['password']);		
		}
        return TRUE;
    }

	/** @access public */
	function addHeader($name,$value){
		$this->headers[$name] = $value;
	}

	function hasError(){
		return is_object($this->error);
	}

	function POST($data){
		if(!$this->hasError())
			return;
		// add custom behavior in descendents
	}
}
