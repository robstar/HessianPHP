<?php


/**
 * Logs proxy activity, including method calling and errors in a defined format.
 * Note that all filter's log messages is static data (global)
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_LogFilter extends Hessian_InterceptingFilter{

	function &getMessages(){
		static $messages = array();
		return $messages;
	}

	function logMessage($msg){
		$messages = &Hessian_LogFilter::getMessages();
		$messages[] = date("Y-m-d H:i:s - ").$msg;
	}

	function init(&$context){
		$msg = &Hessian_LogFilter::getMessages();
		Hessian_LogFilter::logMessage('Initializing Hessian Client for: '.$context->url);
	}

	function execute(&$context,&$chain){
		Hessian_LogFilter::logMessage('Calling method '.$context->callingContext['method']);
		$chain->doFilter($context);
		if($context->error){
			$msg = 'Error in method '.$context->callingContext['method'].' Message is: '.$context->error->message;
			Hessian_LogFilter::logMessage($msg);
		} 
	}
}