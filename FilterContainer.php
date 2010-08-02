<?php


/**
 * Simple class that represents a container for Hessian_InterceptingFilter objects
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_FilterContainer{
	var $filters = array();

	/**
	 * Adds a filter to the container with an optional name. If no name is declared, the name of the class of the filter
	 * will be used instead
	 *  
	 * @param object context The context the filter read and writes to
	 * @access public
	 **/
	function addFilter(&$filter,$name=''){
		if(empty($name))
			$name = get_class($filter);
		$this->filters[$name] = &$filter;
	}

	function removeFilter($name){
		unset($this->filters[$name]);
	}

}