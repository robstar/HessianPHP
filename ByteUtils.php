<?php

/**
 * This is a helper class designed to work with byte conversion and representation of numbers
 * 
 * @package HessianPHP.Protocol
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 **/
class Hessian_ByteUtils{

	/**
	 * Generates big endian byte representation of a number with a defined
	 * precision, 16 or 64 bits for example, default 32 bits.<BR>
	 *  
	 * This function is equivalent to do the transformation by hand with a fixed
	 * bit precision, as in (for a 32 bit representation):<BR>
	 *
	 * <code><BR>
	 * $b32 = $value >> 24;<BR>
	 * $b24 = ($value >> 16) & 0x000000FF;<BR>
	 * $b16 = ($value >> 8) & 0x000000FF;<BR>
	 * $b8 = $value & 0x000000FF;<BR>
	 * $bytes .= pack('c',$b32);<BR>
	 * $bytes .= pack('c',$b24);<BR>
	 * $bytes .= pack('c',$b16);<BR>
	 * $bytes .= pack('c',$b8);<BR>
	 * </code>
	 *
	 * @param long number number to be transformed
	 * @param int precision precision
	 * @return string byte representation of long number
	 * @access public 
	 **/
	function getIntBytes($number,$precision=32){
		switch($precision){
			case 16: $fill = 0x00FF; break;
			case 32: $fill = 0x000000FF; break;
			case 64: $fill = 0x00000000000000FF; break;
		}
		$start = $precision - 8;
		// $sh = bits to shift right
		$bytes = '';
		for($sh = $start ; $sh >= 8 ; $sh = $sh - 8){
			$value = ($number >> $sh) & $fill;
			$bytes .= pack('c',$value);	
		}
		// final byte
		$value = $number & $fill;
		$bytes .= pack('c',$value);
		return $bytes;
	}

	/**
	 * Returns a string with the byte representation of a IEEE 754 double in
	 * 64 bit precision. Works fine between PHP clients and servers but it uses
	 * a machine dependent byte packing representation (pack format "d").
	 *
	 * <B>WARNING:</B> Due to incompatible double formats among different machines, this function
	 * is not guaranteed to return the number with extreme accuracy, specially with periodic fractions
	 * such as 1.3333... Take this in account.<BR>
	 *  
	 * @param double number number to be transformed 
	 * @return string byte representation
	 * @access public 
	 **/
	function getFloatBytes($number) {
		$bin = Hessian_ByteUtils::orderedByteString( pack("d", $number) );  // Machine-dependent size
		// check is deactivated
		/*if(strlen($bin) != 8) {
			echo "Sorry, your machine uses an unsupported double-precision floating point size.";
		}*/
		return $bin;
	}

	/**
	 * Test if this machine is a little endian architecture<BR>
	 * 
	 * Based in code from Open Sound Control (OSC) Client Library for PHP<BR>
	 * Author: Andy W Schmeder &lt;andy@a2hd.com&gt;<BR>
	 * Copyright 2003
	 *
	 * @return boolean is little endian?
	 * @access public 
	 **/
	function isLittleEndian() {
		$machineLong = pack("L", 1);  // Machine dependent
		$indepLong  = pack("N", 1);  // Machine independent
		
		if($machineLong[0] == $indepLong[0])
			return FALSE;
		return TRUE;
	}

	/**
	 * Returns a sequence of bytes in big endian order, it orders the string depending
	 * on machine architecture (big endian or little endian).<BR>
	 * 
	 * Based in code from Open Sound Control (OSC) Client Library for PHP<BR>
	 * Author: Andy W Schmeder &lt;andy@a2hd.com&gt;<BR>
	 * Copyright 2003
	 *
	 * @param string string sequence of bytes to order
	 * @return string big endian ordered sequence of bytes
	 * @access public 
	 **/
	function orderedByteString($string) {
		if(Hessian_ByteUtils::isLittleEndian()) {
			$orderStr = '';
			for($i = 0; $i < strlen($string); $i++) {
				$index = (strlen($string)-1)-$i;
				$orderStr .= $string[$index];
			}
			return $orderStr;
		} 
		// No conversion necessary for big-endian architecture
		return $string;
	}	

}
