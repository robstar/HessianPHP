<?php 



/**
 * Holds the mapping between remote and local object types
 * 
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 **/
class Hessian_TypeMap {
	var $detectedTypes = array();
	var $types = array();

	function __construct(){
		$this->resetTypes();
	}

	function mapRemoteType($remoteType,$localType){
		$this->types[$remoteType] = strtolower($localType); // solo por que es php 4 toca hacerle minusculas
	}

	function getLocalType($remoteType) {
		if(!in_array($remoteType, $this->detectedTypes))
			$this->detectedTypes[] = $remoteType;
		if(class_exists($remoteType))
			return $remoteType;
		if(isset($this->types[$remoteType]))
			return $this->types[$remoteType];
		return false;
	}

	function getRemoteType($localType) {
		$val = array_search(strtolower($localType), $this->types); 
		if($val !== false) {
			return $val;
		}
		return false;
	}

	function resetTypes() {
		$this->types = array();
		$this->types['stdClass'] = 'stdClass';
	}
}