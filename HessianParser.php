<?php


/**
 * <BLOCKQUOTE>
 * Hessian protocol parser, inspired and partially based on hessianlib.py by Caucho.
 * <BR>
 * TODO:
 * <UL>
 *	<LI>Handling of 'headers'</LI>
 * </UL>
 * </BLOCKQUOTE>
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 **/
class Hessian_HessianParser extends Hessian_HessianProtocolHandler{
	var $lastCode = false;
	//var $refs = array();

	/**
	 * Reads n bytes of the stream and increases internal pointer by that number
	 *  
	 * @param int num Number of bytes to read
	 * @return string Bytes read
	 * @access private 
	 **/
	function read($num){
		$byte = substr($this->stream,$this->pos,$num);
		$this->pos += $num;
		return $byte;
	}

	/**
	 * Main parsing function that reads the head code from the stream and returns the appropriate PHP value
	 * Thanks to Radu-Adrian Popescu for his patch to 'long' and 'date' deserializing.
	 *  
	 * @param string code Hessian object code 
	 * @return mixed parsed value
	 **/
	function &parseObject($code=''){
		if($code=='')
			$code = $this->read(1);
		$this->lastCode = $code;
		switch($code):
			case 'N':
				return null;
			case 'F':
				return false;
			case 'T':
				return true;
			case 'I': 
				$data = unpack('N', $this->read(4));
				return $data[1];
			case 'L':
				return $this->readLong(true);
			case 'd':
				$ts = $this->readLong();
				return $this->dateProvider->readDate($ts);
			case 'D':
				/*
				2005-09-14:
				Changed due to the "Fatal error: Only variables can be passed by reference" bug in PHP 5.1 
				
				old code:

				$bytes = ByteUtils::orderedByteString($this->read(8));
				$value = each(unpack("d",$bytes));
				return $value[1];
				
				*/
				$bytes = Hessian_ByteUtils::orderedByteString($this->read(8));
				$val = unpack("d",$bytes);
				$value = array_pop($val);
				return $value;
			case 'B':
			case 'b':
				return $this->readBinary();
			case 'S':
			case 's':
			case 'X':
			case 'x':
				return $this->readString();
			case 'M': 
				return $this->parseMap();
			case 'V': 
				return $this->parseList();
			case 'R':
				$refStruct = unpack('N', $this->read(4));
				$numRef = $refStruct[1];
				if(isset($this->refs[$numRef]))
					return $this->refs[$numRef];
				else
					return new Hessian_HessianError("Unresolved referenced object number $numRef",HESSIAN_PARSER_ERROR,0,$this->stream);
				break;
			case 'z':
				$this->end = true;
				return;
			case 'f':
				return $this->parseFault();
			default:
				return new Hessian_HessianError("Unrecognized response type code '$code' or not implemented",HESSIAN_PARSER_ERROR,0,$this->stream);
		endswitch;
	}

	// Series of parsing method for the different elements in the Hessian spec

	function parseCall(){
		if($this->read(1) != 'c') {
			return new Hessian_HessianError('Hessian Parser, Malformed call: Expected: c',HESSIAN_PARSER_ERROR,0,$this->stream);
		}
		$minor = $this->read(1);
		$major = $this->read(1);

		if($this->read(1) != 'm') {
			return new Hessian_HessianError('Hessian Parser, Malformed call: Expected m',HESSIAN_PARSER_ERROR,0,$this->stream); 
		}
		return $this->parseObject('S');
	}

	function endStream(){
		if($this->pos == $this->len){
			$this->end = true;
			return true;
		}
		return false;
	}

	function parseReply(){
		if($this->read(1) != 'r') {
			return new Hessian_HessianError('Hessian Parser, Malformed reply: expected r',HESSIAN_PARSER_ERROR,0,$this->stream);
		}
		$minor = $this->read(1);
		$major = $this->read(1);
		$value = $this->parseObject($this->read(1));
		if($this->read(1) == 'z')
			return $value;
	}

	function &parseFault(){
		$code = $this->read(1);
		$fault = array();
		// OJO: que quise hacer aqui?
		$map = array();
		$this->refs[] = &$map;
		while($code != 'z'){
			$key = &$this->parseObject($code);
			$value = &$this->parseObject();
			$map[$key] = $value;
			$code = $this->read(1);
		}
		$faultMessage = 'Service fault';
		if(isset($map['code']) && isset($map['message'])) {
			$faultMessage .= ': '.$map['message'];
			unset($map['message']);
		} 
		return new Hessian_HessianError("Hessian Fault: $faultMessage",HESSIAN_FAULT,$map,$this->stream);

	}

	function &parseMap(){
		if($this->read(1)!='t') {
			return new Hessian_HessianError('Malformed map format: expected t',HESSIAN_PARSER_ERROR,0,$this->stream);
		}
		$type = $this->readString();
		$code = $this->read(1);
		//$localType = TypeMap::getLocalType($type);
		$localType = $this->typeMap->getLocalType($type);
		if(!$localType)
			$map = array();	
		else {
			$map = &new $localType;
		}
		$this->refs[] = &$map;
		while($code != 'z'){
			$key = &$this->parseObject($code);
			$value = &$this->parseObject();
			if(!$localType)
				$map[$key] = $value;
			else
				$map->$key = $value;
			$code = $this->read(1);
		}
		return $map;
	}

	function &parseList(){
		$code = $this->read(1);
		// read type if exists
		if($code == 't'){ 
			$type = $this->readString();
			$code = $this->read(1);
		}
		// read list length if exists
		if($code == 'l') {
			$lenStruct = unpack('N', $this->read(4));
			$len = $lenStruct[1];
			$code = $this->read(1);
		}
		$list = array();		
		$this->refs[] = &$list;
		while($code != 'z'){
			$list[] = &$this->parseObject($code); 
			$code = $this->read(1);
		}
		return $list;
	}

	function readLong(){
		// Thanks Radu-Adrian Popescu
		$data = unpack('N2', $this->read(8));
		$value = $data[1]*256*256*256*256 + $data[2]; // +0.0; 
		return $value;
	}

	function readString(){
		$end = false;
		$string = '';
		while(!$end) {
			$tempLen = unpack('n',$this->read(2));
			$len = $tempLen[1];
		
			if($this->lastCode == 's' || $this->lastCode == 'x') {
				$this->lastCode = $this->read(1);
			} else
				$end = true;
			
			// Some UTF8 characters are represented with more than one byte to we need
			// to read every character to find out if we need to read in advance.
			for($i=0;$i<$len;$i++){
				$ch = $this->read(1);
				$charCode = ord($ch);
				if($charCode < 0x80)
					$string .= $ch;
				elseif(($charCode & 0xe0) == 0xc0){
					$string .= $ch.$this->read(1);
				} elseif (($charCode & 0xf0) == 0xe0) {
					$string .= $ch.$this->read(2);
				} else {
					return new Hessian_HessianError("Bad utf-8 encoding",HESSIAN_PARSER_ERROR,0,$this->stream);
				}
			}
			//$end = true;
		}
		return utf8_decode($string);
	}

	function readBinary(){
		$end = false;
		$data = '';
		while(!$end) {
			$bytes = $this->read(2);
			$tempLen = unpack('n',$bytes);
			$len = $tempLen[1];
			$data .= $this->read($len);
			if($this->lastCode == 'b') {
				$this->lastCode = $this->read(1);
			} else
				$end = true;
		}
		return $data;
	}

}