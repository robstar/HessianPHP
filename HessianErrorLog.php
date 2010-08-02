<?php

/**
 * This is an error handling class that receives notifications from the clients
 * when an error occurs and notifies the user code. Implements the observer pattern.
 *
 * @package HessianPHP
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/

class Hessian_HessianErrorLog{
	var $errorStack= array();
	var $errorLevel;

	function __construct(){
		$this->clear();
		if (phpversion() < 5)
			$this->errorLevel = HESSIAN_SILENT;
		else
			$this->errorLevel = HESSIAN_EXCEPTION;
	}

	function notifyError($error){
		$this->errorStack[] = $error;
		switch($this->errorLevel){
			// Here's one of those ugly things you have to do in life sometimes
			case HESSIAN_EXCEPTION: 
				if( phpversion() >= 5 )	
					eval('throw $error;'); 
				break; 
			case HESSIAN_TRIGGER_NOTICE: trigger_error($error->message); break;
			case HESSIAN_TRIGGER_ERROR: trigger_error($error->message,E_USER_ERROR); break;
			default: break; // silent
		}
	}

	function clear(){
		$this->errorStack = array();
	}

	function getLastError(){
		if(!empty($this->errorStack)){
			$last = count($this->errorStack) -1;
			return $this->errorStack[$last];
		}
		return false;
	}
}
