<?php

include_once 'Protocol.php';
include_once 'Hessian.php';
include_once 'Http.php';

/**
 * Displays a web page with information about the real service objects and handles calls to it's methods
 * PHP5 version uses the new Reflection API
 * 
 * @package HessianPHP.Server
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_ServiceInfoPHP4{
	var $service;
	var $name;
	var $underscoreInclude = false;

	/**
	 * Registers the wrapped object that will perform the methods of the web service.
	 *  
	 * @param object service Real service object 
	 * @access public 
	 **/
	function registerObject(&$service){
		$this->service = &$service;
		if($this->name == '')
			$this->name = get_class($service);
	}

	function displayInfo(){
		if(!is_object($this->service)) return;
		$methods = get_class_methods(get_class($this->service));
		//foreach($methods as $method)
		//	echo $method.'<br>';
		$methodTpl = '
        <li>
           <a href="#">#methodname</a>

        </li>
        <p>';
		$html = @file_get_contents(dirname(__FILE__).'/serviceInfo.tpl'); 
		if(!$html) {
			$html = '
				<html> <head> <title>#service Hessian Service</title></head> <body>
				<h2><strong>#service</strong></h2>
				<p>Powered by HessianPHP #version</p>
				<p>This is a list of supported operations in this service.<p>
				<ul>#methods</ul>
				<body></html>';
		}
		$methodHtml = '';
		foreach($methods as $method){
			$methodHtml .= str_replace('#methodname', $method, $methodTpl);
		}
		$html = str_replace('#methods', $methodHtml, $html);
		$html = str_replace('#service', ucfirst($this->name), $html);
		$html = str_replace('#version', HESSIAN_PHP_VERSION, $html);
		echo $html;
	}

	/**
	 * Dynamically calls a method in the wrapped object passing parameters from the request<BR>
	 * and returns the result. Generates a fault if the method does not exist.
	 *  
	 * @param string method Name of the method
	 * @param array params Array of parameters to be passed
	 * @return mixed Returned value from the service or null if fault
	 * @access protected 
	 **/
	function &callMethod($method,&$params,&$writer){
		if(!$this->isMethodCallable($method)){
			$writer->setFault('1',"Method $method does not exist in this service");
			return null;
		}
		return call_user_func_array(array($this->service,$method),$params);
	}

	function isMethodCallable($method){
		if(!method_exists($this->service,$method))
			return false;
		if(strpos($method,'_') === 0 && !$this->underscoreInclude) // wheter to include old style private methods
			return false;
		return true;
	}

}