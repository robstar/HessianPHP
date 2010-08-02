<?php

/**
 * Simple chain of responsibility implementation that executes a series of filters in order
 * 
 * @package HessianPHP.Filters
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_FilterChain{
	var $filters;
	var $current;

	function __construct(&$filters){
		foreach($filters as $key=>$value){
			$this->filters[] = &$filters[$key];
		}
		//$this->filters = &$filters;
		$this->current = -1;
	}

	function isChainDone(){
		return $this->current >= count($this->filters);
	}

	/**
	 * Recursive method that continues the execution of the next filter in the chain
	 * Includes two chain finalization checks before and after the filter execution
	 * because an explicit call to doFilter() inside the filter object can end the
	 * execution
	 *  
	 * @param mixed context context the filter works with
	 **/
	function doFilter(&$context){
		$this->current++;
		if($this->current >= count($this->filters)) {
			return;
		}
		$next = &$this->filters[$this->current];
		$next->execute($context,$this);
		if($this->current >= count($this->filters)) {
			return;
		}
		$this->doFilter($context);
	}
}