<?php

/**
 * This class gives HessianParser and HessianWriter a way to extract timestamps from Datetime objects
 * and to return new Datetime objects from timestamps that are deserialized from a Hessian reply.
 *
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/

class Hessian_DefaultDateProvider{

	function __construct(){
	}

	function isDateObject(&$object){
		return is_a($object,'Hessian_DateTime');
	}

	function readDate($timestamp){
		return new Hessian_DateTime($timestamp / 1000);
	}

	function writeDate(&$dateobj){
		return $dateobj->getTimestamp() * 1000; // +0.0;
	}

}
