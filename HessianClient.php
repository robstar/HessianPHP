<?php
class Hessian_HessianClient {

	/**
	 * Constructor, requires the url of the remote Hessian service
	 *  
	 * @param string url Url of the remote service
	 **/
	function __construct($url,$options=false){
		$this->__hessian__proxy__ = &Hessian_Hessian::getHessianProxy($url,$options);
	}
	
	/**
	 * PHP magic function used to execute a remote call to a remote Hessian service.
	 *  
	 * @param string method Method name
	 * @param array params Arguments
	 * @return mixed True if PHP 4, return value of the function if PHP 5
 	 * @access public 
	 **/
	function __call($method,$params){
		return $this->__hessian__proxy__->call($method,$params);
	}
}