<?php

/**
 * Base class for Hessian protocol handling objects. Contains methods to handle streams, references, mapping of classes 
 * and datetimes
 * 
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_HessianProtocolHandler{
	var $stream;
	var $pos;
	var $len;
	var $refs = array();
	var $error;
	var $dateProvider;
	var $typemap;

	function __construct($stream=null){
		if($stream)
			$this->setStream($stream);
		// Recover default configuration data
		$config = &Hessian_HessianConfig::globalConfig();
		$this->setTypeMap($config->typeMap);
		$this->setDateProvider($config->dateProvider);
	}
	
	function clearRefs(){
		$this->refs = array();
	}
	
	/**
	 * Sets the stream of bytes to parse
	 *  
	 * @param string stream Incoming stream
	 **/
	function setStream($stream){
		$this->stream = $stream;
		$this->len = strlen($stream);
		$this->pos =0;
	}

	function setDateProvider(&$provider){
		$this->dateProvider = $provider;
	}

	function setTypeMap(&$map){
		$this->typeMap = &$map;
	}

}