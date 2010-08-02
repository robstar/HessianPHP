<?php
/*
-------------------------------------------------------------
HessianPHP - Binary Web Services for PHP

Copyright (C) 2004-2005  by Manolo G�mez
http://www.hessianphp.org

Hessian Binary Web Service Protocol by Caucho(www.caucho.com) 

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

You can find the GNU General Public License here
http://www.gnu.org/licenses/lgpl.html
or in the license.txt file in your source directory.

If you have any questions or comments, please email:
vegeta.ec@gmail.com

*/



/**
 * <BLOCKQUOTE>
 * Enables a common PHP object to be published as a Hessian compatible web-service via a POST request.
 * It wraps a PHP object that executes the real methods passed by the HessianService class.
 *
 * Example usage:<BR>
 * <BR>
 * <code>
 * $wrapper = &new HessianService();<BR>
 * $wrapper->registerObject(new ServiceObject());<BR>
 * $wrapper->service();<BR>
 * </code>
 * <BR>
 * Then clients can perform method calls to the url of the service, ex. 
 * http://localhost/hessian/testservice.php
 * 
 * TODO: <BR>
 * <UL>
 *	<LI>Support for the _hessian* magic methods</LI>
 * </UL>
 *</BLOCKQUOTE>
 * @package HessianPHP.Server
 * @author Manolo G�mez
 * @copyright Copyright (c) 2004
 * @version 1.0
 * @access public
 **/
class Hessian_HessianService {
	var $serviceInfo;
	var $fault = false;
	var $writer;
	var $parser;
	var $displayInfo = false;
	var $errorReporting;
	var $_restoreError;

	function __construct($name=''){
		$this->writer = &new Hessian_HessianWriter();
		$this->parser = &new Hessian_HessianParser();

		if(phpversion() < 5){
			$this->serviceInfo = new Hessian_ServiceInfoPHP4($name);
		} else {
			$this->serviceInfo = new Hessian_ServiceInfoPHP5($name);
		}

		// parse options
		if(is_array($name)){
			if(!empty($name['displayInfo']))
				$this->displayInfo = $name['displayInfo'];
			if(!empty($name['underscoreInclude']))
				$this->serviceInfo->underscoreInclude = $name['underscoreInclude'];
			if(!empty($name['name']))
				$this->serviceInfo->name = (string)$name['name'];
		}
		$this->errorReporting = E_ALL ^ E_NOTICE;
	}

	function registerObject(&$service){
		$this->serviceInfo->registerObject($service);
	}

	/**
	 * Publishes the service, check incoming calls and routes them to the wrapped object.<BR>
	 * This method uses streams to retrieve raw POST bytes and a 
	 * {@link HessianPHP.HessianParser HessianParser} and {@link HessianPHP.HessianWriter HessianWriter}
	 * to execute the call and send results back to the client.
	 *
	 * As defined in Hessian 1.0 spec, the service requires POST to execute. It is advised not to call<BR>
	 * any other php code that writes to the default screen output (echo, print, etc.) as it can corrupt
	 * the reply.
	 *  
 	 * @access public 
	 **/
	function service(){
		if(!is_object($this->serviceInfo->service)){
			header("HTTP/1.0 500 Hessian not configured");
			die('Serviced object not registered!');
		}
		if($_SERVER['REQUEST_METHOD'] != 'POST') {
			if($this->displayInfo) {
				$this->serviceInfo->displayInfo();
				exit();
			} else {
				header("HTTP/1.0 500 Hessian Requires POST");
				die('<h1>Hessian Requires POST</h1>');
			}
		}
		ob_start(); // apparently it wins a few milliseconds

		// handle errorReporting
		if($this->errorReporting) {
			$this->_restoreError = error_reporting($this->errorReporting);
		}

		// uso de streams para obtener informaci�n cruda del post
		$ph = fopen("php://input", "rb");
		$postData = '';
		while (!feof($ph))
		{
			$postData .= fread($ph, 4096);
		}
		fclose($ph);
		$this->parser->setStream($postData);

		$method = &$this->parser->parseCall();
		$result = null;
		if(Hessian_Hessian::isError($method)){
			$this->writer->setFault($method->code, $method->message, $method->getError());
		} else {
			$params = array();
			$error = false;
			while(!$this->parser->endStream()){
				$param = &$this->parser->parseObject();
				if(Hessian_Hessian::isError($param)){
					$this->writer->setFault($param->code, $param->message, $param->getError());
					$error = true;
					break;
				} else {
					$params[] = &$param;
				}
			}
			// little hack to get rid of the finishing code 'z' in the parameter list
			if(!$error){
				$last = count($params)-1;
				unset($params[$last]);
				// end hack
				$result = $this->serviceInfo->callMethod($method,$params,$this->writer);
			}
		}
		$reply = trim($this->writer->writeReply($result));


		header('Content-type: application/binary');
		header('Content-length: ' . strlen($reply));
		header('Connection: close');

		$nfp = fopen("php://output","wb+");
		fwrite($nfp, trim($reply));
		fclose($nfp);
		//echo $reply;
		ob_end_flush();

		// restore error reporting level
		if($this->errorReporting) {
			error_reporting($this->_restoreError);
		}
	}

}

?>