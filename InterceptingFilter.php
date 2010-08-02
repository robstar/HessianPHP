<?php


/**
 * Filter base class
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_InterceptingFilter{
	/**
	 * Filter implementation goes here. Depending of the position of the call to $chain->doFilter($context);
	 * this can be a before, after or around type of filter.
	 * If the call is never executed, the default behaviour is a before filter.
	 *  
	 * @param object context The context the filter read and writes to
	 * @param Hessian_FilterChain chain The filter chain to be followed
	 * @access public
	 **/
	function execute(&$context,&$chain){}
	/**
	 * Performs optional initialization tasks in the filter whenever is assigned to a HessianClient
	 *  
	 * @param object context The context the filter read and writes to
	 * @access public
	 **/
	function init(&$context){}
}