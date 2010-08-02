<?php

// Default filters

/**
 * Filter that configures php's error reporting mechanism to all but notices.
 * This filter is necessary to work with PHP 4.4.x and newer PHP 5 versions 
 * until Protocol.php is refactored to work with references the way
 * these platforms require or they remove the notice :).
 * 
 * This filter is enabled by default
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_PHPErrorReportingFilter extends Hessian_InterceptingFilter{
	var $error;
	var $prevError;

	function __construct(){
		$this->error = E_ALL ^ E_NOTICE;
	}

	function execute(&$context,&$chain){
		$this->prevError = error_reporting($this->error);
		$chain->doFilter($context);
		error_reporting($this->prevError);
	}
}
