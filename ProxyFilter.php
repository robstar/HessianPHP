<?php


/**
 * Filter that wraps the HessianClient actual call. It is required by the framework to operate correctly
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_ProxyFilter extends Hessian_InterceptingFilter{
	var $method,$params,$result;
	
	function __construct($method,&$params){
		$this->method = $method;
		$this->params = &$params;
	}

	function execute(&$context,&$chain){
		$this->result = $context->executeCall($this->method,$this->params);
		$context->callingContext['result'] = $this->result;
		$chain->doFilter($context);
	}
}
