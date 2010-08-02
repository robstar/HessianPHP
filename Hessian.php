<?php

/**
 * Main client configuration class. Use it's static methods to configure several options for the connection to a url
 * and mapping of remote types to php types via deserializers.
 * See HessianPHP tests for examples of usage.
 * 
 * @package HessianPHP
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_Hessian {

	/**
	 * Returns an object representing information on a remote Hessian url.
	 * Optionally, it can create a default object if needed
	 *  
	 * @param string url Remote url
	 * @param boolean create Shall it create the object?
 	 * @access public 
	 * @static
	 **/
	function &getHessianProxy($url,$options=false){
		/*$config = &HessianConfig::globalConfig();
		if(!isset($config->proxies[$url])) {
			$proxy = &new Hessian_HessianProxy($url,$options);
			$config->proxies[$url] = &$proxy;
		}
		$proxy = &$config->proxies[$url];
		$proxy->options = $options;
		return $proxy;*/
		$proxy = new Hessian_HessianProxy($url,$options);
		return $proxy;
	}

	/**
	 * Registers a remote method name for a service url. 
	 *  
	 * @param string url Url of the service
	 * @param string name Name of the remote service method 
 	 * @access public 
	 * @static
	 **/
	function remoteMethod($url,$name){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->remoteMethodUrl($url,$name);
	}

	/**
	 * Sets a mapping between a remote object type to a php object type.
	 * It maps the class name received in a service to a local php class to be created
	 * and deserialized by the default object deserializer.
	 * Usefull to match types from different platforms to custrom php classes in a simple way.  
	 *
	 * @param string remoteType Name of the remote Class
	 * @param string phptype Name of the PHP class to create
 	 * @access public 
	 * @static
	 **/
	function mapRemoteType($remoteType,$phptype){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->typeMap->mapRemoteType($remoteType,$phptype);
	}

	function setDateProvider(&$provider){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->dateProvider = &$provider;
	}

	function &getDateProvider(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->dateProvider;
	}

	function errorReporting($level=null){
		$config = &Hessian_HessianConfig::globalConfig();
		if($level!=null)
			$config->errorLog->errorLevel = $level;
		return $config->errorLog->errorLevel;
	}

	function error(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->errorLog->getLastError();
	}

	function getErrorStack(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->errorLog->errorStack;
	}

	function isError($obj){
		if( is_a($obj, 'Hessian_HessianError') || is_a($obj, 'HttpError') )
			return true;
		return false;
	}

	function addFilter(&$obj,$name=''){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->addFilter($obj,$name);
	}

	function removeFilter($name){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->removeFilter($name);
	}
}

