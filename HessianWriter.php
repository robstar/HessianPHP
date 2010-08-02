<?php


/**
 * <BLOCKQUOTE>
 * Hessian protocol writer, inspired and partially based on hessianlib.py by Caucho.
 * <BR>
 * TODO:
 * <UL>
 *	<LI>Handling of _hessian_write function</LI>
 * </UL>
 * </BLOCKQUOTE>
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 **/
class Hessian_HessianWriter extends Hessian_HessianProtocolHandler{
	//var $stream;
	var $fault = false;
	//var $refs = array();

	/**
	 * Sets the reply as a fault, following Hessian spec
	 *  
	 * @param string code Code number of the fault
	 * @param string message Descriptive message of the fault
	 * @param mixed detail Optional argument with detail of the fault, usually a stack trace
	 * @access public
	 **/
	function setFault($code,$message,$detail=null){
		$this->fault = array('code' => $code, 'message' => $message, 'detail' => $detail);
	}

	/**
	 * Serializes a PHP value into a Hessian stream using reflection. Depending on the type
	 * it calls one of the writing functions of this class.
	 *  
	 * @param mixed value Value to be serialized
	 **/
	function writeObject(&$value){
		$type = gettype($value);
		switch($type){
			case 'integer': $dispatch = 'writeInt' ;break;
			case 'boolean': $dispatch = 'writeBool' ;break;
			case 'string': $dispatch = 'writeString' ; break;
			case 'double': $dispatch = 'writeDouble' ; break;
			case 'array': 
				if($this->isArrayAssoc($value)) {
					$dispatch = 'writeMap';
				} else {
					$dispatch = 'writeList';
				}	
				break;
			case 'object': $dispatch = 'writeMap' ;break;
			case 'NULL': $this->stream .= 'N' ;return;
			case 'resource': $dispatch = 'writeResource' ; break;
			default: die("$type not implemented");
		}
		$this->$dispatch($value);
	}

	/**
	 * Writes a Hessian reply with a return object. If a fault has been set, it writes the fault instead
	 *  
	 * @param mixed object Object to be returned in the reply
	 * @return string Hessian reply
	 **/
	function writeReply($object){
		$stream = &$this->stream;
		$stream = "r\x01\x00";
		if(!$this->fault) {
			$this->writeObject($object);
		} else {
			$this->writeFault($this->fault['code'],
				$this->fault['message'],
				$this->fault['detail']);

		}
		$stream .= "z";
		return $stream;
	}

	/**
	 * Writes a Hessian method call and serializes arguments.
	 *  
	 * @param string method Method to be called
	 * @param array params Arguments of the method
	 * @return string Hessian call
	 **/
	function writeCall($method,&$params){
		$stream = &$this->stream;
		$stream = "c\x01\x00m";
		$this->writeStringData($method);
		foreach($params as $param){
			$this->writeObject($param);
		}
		$stream .= "z";
		return $stream;
	}

	// Series of Hessian object serializing functions

	function writeBool($value){
		if($value) $this->stream .= 'T';
		else $this->stream .= 'F';
	}

	function writeString($value){
		$this->stream .= 'S';
		$this->writeStringData($value);
	}

	function writeHeader($value){
		$this->stream .= 'H';
		$this->writeStringData($value);
	}

	function writeBytes($value){
		$this->stream .= 'B';
		// OJO tal vez no haga falta escribir como string
		$this->writeStringData($value);
	}

	function writeFault($code,$message,$detail){
		$this->stream .= 'f';
		$this->writeString('code');
		$this->writeString($code);
		$this->writeString('message');
		$this->writeString($message);
		// OJO puede ser false o null o lo que sea, por lo pronto no es null
		if(!is_null($detail)){
			$this->writeString('detail');
			$this->writeObject($detail);
		}
		$this->stream .= 'z';
	}

	function writeInt($value){
		$this->stream .= 'I';
		//$this->stream .= pack('N',$value);
		$this->stream .= Hessian_ByteUtils::getIntBytes($value,32);
	}

	function writeLong($value){
		$this->stream .= 'L';
		$less = $value>>32;
		$res = $value / pow(2,32);
		$this->stream .= pack('N2',$res,$less);
		//$this->stream .= ByteUtils::getIntBytes($value,64);
	}

	function writeDate($value){
		$this->stream .= 'd';
		$less = $value >> 32;
		$res = $value / pow(2,32); // 256/256/256/256; 
		/*
		printf("%X<br>",$less);
		printf("%X<br>",$res);
		$st = pack('N',$res);
		$st .= pack('N',$less);
		$this->stream .= $st;*/
		$this->stream .= pack('N2',$res,$less);
		
	}

	// OJO que no se sabe si la representacion interna de PHP sea 64 bit IEEE 754
	function writeDouble($value){
		$this->stream .= 'D';
		$this->stream .= Hessian_ByteUtils::getFloatBytes($value);
	}

	function writeStringData($value){
		$this->stream .= pack('n',strlen($value));
		$this->stream .= utf8_encode($value);
	}


	/**
	 * Checks internal reference map to see if an object has already been written to output stream.
	 * If it has, it only writes a reference to it and returns true, otherwise returns false
	 *  
	 * WARNING: in PHP4, don't use circular references or this function will crash!
	 *
	 * @param mixed value object
	 * @return boolean is reference writen?
	 **/
	function writeReference(&$value){
		// really ugly way to find if an object reference exists, should be better in PHP 5
		$i=0;
		$total = count($this->refs);
		while($i<$total){
			if($value === $this->refs[$i]){
				$this->stream .= 'R';
				$this->stream .= Hessian_ByteUtils::getIntBytes($i,32);
				return true;
			} 
			$i++;
		}
		// if not found insert in reference array;
		$this->refs[] = $value;
		return false;
	}

	function writeList(&$value){
		if($this->writeReference($value)) 
			return;
		$this->stream .= 'V';
		// type, maybe we don't need type info since this is PHP
		$this->stream .= 't';
		$this->writeStringData('');
		// end type info
		if(!empty($value)){
			$this->stream .= 'l';
			$this->stream .= Hessian_ByteUtils::getIntBytes(count($value),32);
			foreach($value as $val){
				$this->writeObject($val);
			}
		}
		$this->stream .= 'z';
	}

	function writeMap(&$value){

		// Datetime Object resolution
		/*$dateProvider = &Hessian::getDateProvider();
		if($dateProvider->isDateObject($value)){
			$ts = $dateProvider->writeDate($value);
			return $this->writeDate($ts);
		}*/

		if($this->dateProvider->isDateObject($value)){
			$ts = $this->dateProvider->writeDate($value);
			return $this->writeDate($ts);
		}

		if($this->writeReference($value)) 
			return;
		$this->stream .= "M";
		// type handling for local classes
		$this->stream .= 't';
		if(is_object($value)) {
			$localType = get_class($value);
			//$type = TypeMap::getRemoteType($localType);
			$type = $this->typeMap->getRemoteType($localType);
			if(!$type) $type = $localType;
			$this->writeStringData($type);
		}
		else
			$this->writeStringData('');
		if(!empty($value)){
			if(is_array($value)) {
				// arrays
				foreach($value as $key => $val){
					$this->writeObject($key);
					$this->writeObject($val);
				}
			}
			if(is_object($value)) {
				// classes
				$vars = get_object_vars($value);
				foreach($vars as $varName => $varValue){
					$this->writeObject($varName);
					$this->writeObject($value->$varName);
				}
			}
		}
		$this->stream .= 'z';
	}

	/**
	 * Very simple way to check if an array is associative. PHP doesn't have a way to tell
	 * an associative array from one that only has numbers as keys.
	 * Never mind the foreach, it's *faster* than other ways.
	 * Stops when a key is of string type or the key is negative, yes, you are read it well,
	 * array keys can be negative (and also null, and false, and...)
	 *  
	 * @param array array Array to check
	 * @return boolean is associative?
	 **/
	function isArrayAssoc(&$array){
		if(empty($array))
			return false;
		foreach($array as $key => $val) {
			if (is_string($key) || $key<0) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * EXPERIMENTAL: Algorithm to check if a php associative array is *exactly* an
	 * ordered list. It uses a property of ordered lists numeric keys, they correspond
	 * to the 0,1,2...n series of continuous integers, therefore you can check if
	 * an array is an ordered list by calculating the sum of its keys by hand and then
	 * using a formula. If both values match, it is an ordered list.
	 *   
	 * Just Slightly slower than isArrayAssoc but safer. (currently not being used)
	 *
	 * @param array array Array to check
	 * @return boolean is an ordered list?
	 **/

	function isList(&$array){
		if(empty($array))
			return false;
		$phpSum = 0;
		foreach($array as $key => $val){ // foreach is faster
		//while(list($key) = each($array)){
			if (!is_int($key) || $key<0) return false;
			$phpSum += $key;
		}
		$n = count($array);
		// formula para calcular la sumatoria de una serie
		$sum = (0*$n) + ( ($n*($n-1)*1)/2 );
		if($sum == $phpSum)
			return true;
		return false;
	}

	function writeResource($handle){
		$type = get_resource_type($handle);
		if($type == 'file' || $type == 'stream'){
			while (!feof($handle)) {
				$content = fread($handle, 32768);
				$tag = 'b';
				if(feof($handle))
					$tag = 'B';
//				echo var_dump(strlen($content)).'<br>';
				$this->stream .= $tag . pack('n',strlen($content));
				$this->stream .= $content;
			}
			fclose($handle);
		} else {
			return new Hessian_HessianError("Cannot handle resource of type '$type'",HESSIAN_WRITER_ERROR);	
		}
	}

}