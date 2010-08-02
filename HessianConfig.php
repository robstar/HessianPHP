<?php

/**
 * Global library consiguration class. It holds objects shared by all client instances
 * and by the protocl handler classes.
 * It can be instanced but it's main use it through the singleton globalConfig() function
 * 
 * @package HessianPHP
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/

class Hessian_HessianConfig extends Hessian_FilterContainer{
	var $typeMap;
	var $dateProvider;
	var $errorLog;
	var $remoteMethods = array();

	function __construct(){
		$this->typeMap = &new Hessian_TypeMap();
		$this->dateProvider = &new Hessian_DefaultDateProvider();
		$this->errorLog = &new Hessian_HessianErrorLog();
	}

	function remoteMethodUrl($url,$name){
		$phpmethod = strtolower($name);
		$this->remoteMethods[$url][$phpmethod] = $name;
	}

	/**
	 * Returns the singleton instance of the class and sets default configuration.
	 *  
	 * @return HessianConfig singleton
	 * @access protected
	 * @static
	 **/
	function &globalConfig(){
		static $config = null;
		if(!$config){
			$config = new Hessian_HessianConfig();
		}
		return $config;
	}
}