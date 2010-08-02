<?php

/**
 * Represents a remote Hessian service endpoint with things such as
 * url, remote methods, security and several connection options
 * 
 * @package HessianPHP.Client
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_HessianProxy extends Hessian_FilterContainer{
	var $url;
	var $remoteMethods = array();
	var $options;
	var $error = false;
	var $parser;
	var $writer;
	var $http;
	var $callingContext = array();

	function __construct($url,$options=false){
		$this->url = $url;
		$this->writer = &new Hessian_HessianWriter();
		$this->parser = &new Hessian_HessianParser();
		$this->http = &new Hessian_SocketHttpConnection($url,$options);
		$this->http->addHeader('Content-type','application/binary');
		$this->options = $options;
		$config = &Hessian_HessianConfig::globalConfig();
		$this->errorLog = &$config->errorLog;
		
		// general options
		if(!empty($config->remoteMethods[$url])){
			foreach($config->remoteMethods[$url] as $method){
				$this->remoteMethod(trim($method));
			}
		}
		// local methods
		if(isset($options['methods'])){
			$methods = split(',',$options['methods']);
			foreach($methods as $method){
				$this->remoteMethod(trim($method));
			}
		}
		// global filter initialization
		if(!empty($config->filters)){
			foreach($config->filters as $key=>$value){
				// <<<<<< EXPERIMENTAL >>>>>>
				// This is a way to use the global filters in a per proxy fashion
				// although this isn't required since we can use filters defined in the options, see below
				/*
				if(phpversion() < 5)
					$fil = $value; // copy
				else
					eval('$fil = clone $value;');

				$fil->init($this);
				$this->filters[$key] = $fil;
				*/
				$filter = &$config->filters[$key];
				$filter->init($this);
				//$this->filters[$key] = $value;
				$this->addFilter($filter,$key);
			}
		}
		// per proxy filter configuration
		if(isset($options['filters']) && is_array($options['filters'])){
			foreach($options['filters'] as $key=>$value){
				if(!is_object($value)) {
					$this->notifyError(new Hessian_HessianError('Incorrect filter definition format'));
					break;
				}
				$filter = &$options['filters'][$key];
				$filter->init($this);
				$this->addFilter($filter,$key);
			}
		}
	}

	/**
	 * Registers a remote method name. Useful for store description of services
	 * and resolve naming conflicts due to case sensitivity
	 *  
	 * @param string name Name of the remote method
	 * @access public
	 **/
	function remoteMethod($name){
		$phpmethod = strtolower($name);
		$this->remoteMethods[$phpmethod] = $name;
	}

	/**
	 * Returns the exact case sesitive name of a registered remote method
	 *  
	 * @param string method case insensitive name of the method
	 * @return string case sensitive name of the method
	 * @access public
	 **/
	function resolveMethod($method){
		$checkMethod = strtolower($method);
		// ugly
		$config = &Hessian_HessianConfig::globalConfig();
		if(isset($config->remoteMethods[$this->url][$checkMethod])) {
			return $config->remoteMethods[$this->url][$checkMethod];
		}
		
		if(isset($this->remoteMethods[$checkMethod]))
			return $this->remoteMethods[$checkMethod];
		return $method;
	}
	
	/**
	 * Sets a connection option that will be passed to the Hessian proxy
	 * when called. Format is a pair key/value
	 *  
	 * @param string name Key
	 * @param string value Value
	 * @access public
	 **/
	function setOption($name,$value){
		$this->options[$name] = $value;
	}

	function getOption($name){
		if(isset($this->options[$name]))
			return $this->options[$name];
		return false;
	}	

	/**
	 * Performs a remote call taking in account whatever filters have been defined for this proxy
	 *  
	 * @param string method name of the remote method
	 * @param array params Array containing the values to send
	 * @access public
	 **/
	function call($method,$params){
		$this->error = null;
		$this->callingContext = array('method'=>$method,'params'=>$params,'result'=>null);
		if(empty($this->filters))
			return $this->executeCall($method,$params);

		$wrapper = &new Hessian_ProxyFilter($method,$params);
		$this->filters['__default__'] = &$wrapper;
		$chain = &new Hessian_FilterChain($this->filters);
		$chain->doFilter($this);
		return $wrapper->result;
	}

	/**
	 * Performs the actual remote call
	 *  
	 * @param string method name of the remote method
	 * @param array params Array containing the values to send
	 * @access public
	 **/
	function executeCall($method,$params){
		if($this->http->hasError()) {
			return $this->notifyError($this->http->error);
		}

		$this->writer->clearRefs();

		$method = $this->resolveMethod($method);
		$data = &$this->writer->writeCall($method,$params);
		
		if(Hessian_HessianError::isError($data)){
			return $this->notifyError($data);
		}

		$reply = $this->http->POST($data);
		if($this->http->hasError()) {
			return $this->notifyError($this->http->error);
		}

		$this->parser->setStream($reply);
		$this->parser->clearRefs();
		$result = &$this->parser->parseReply();
		if(Hessian_HessianError::isError($result)){
			return $this->notifyError($result);
		}
		return $result;
	}

		/**
	 * Notifies the Hessian_HessianErrorLog object that handles the error
	 *  
	 * @param Object error An error object
	 * @return boolean always false, as it denotes an error
	 * @access public
	 **/

	function notifyError($error){
		$this->error = $error;
		$this->errorLog->notifyError($error);	
		return false;
	}
}
