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
 * Displays a web page with information about the real service objects and handles calls to it's methods
 * PHP5 version uses the new Reflection API
 * 
 * @package HessianPHP.Server
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_ServiceInfoPHP5{
	var $service;
	var $info;
	var $name;
	var $underscoreInclude = false;

	/**
	 * Registers the wrapped object that will perform the methods of the web service.
	 *  
	 * @param object service Real service object 
	 * @access public 
	 **/
	public function registerObject($service){
		if(is_object($service)){
			$this->service = $service;
			$this->info = new ReflectionObject($service);
			if($this->name == '')
				$this->name = get_class($service);
		}
	}

	function displayInfo(){
		if(!is_object($this->service)) return;
		//$methods = get_class_methods(get_class($this->service));
		$methods = $this->info->getMethods();
		$methodTpl = '<li> <a href="#">#methodname</a> </li> <p>';
		$html = @file_get_contents(dirname(__FILE__).'/serviceInfo.tpl'); 
		if(!$html) {
			$html = '
				<html> <head> <title>#service Hessian Service</title></head> <body>
				<h2><strong>#service</strong></h2>
				<p>Powered by HessianPHP #version</p>
				<p>This is a list of supported operations in this service.<p>
				<ul>#methods</ul>
				<body></html>';
		}
		$methodsHtml = '';
		foreach($methods as $method){
			if($this->isMethodCallable($method)) {
				$text = $method->getName() .' (';
				$params = $method->getParameters();
				$paramText = array();
				foreach($params as $param){
					$class = $param->getClass();
					if($class)
						$paramText[] = $param->getClass()->getName().' '.$param->getName();
					else
						$paramText[] = ' '.$param->getName();
				}
				$text .= implode(',',$paramText).')';
				$methodsHtml .= str_replace('#methodname', $text , $methodTpl);
			}
		}
		$html = str_replace('#methods', $methodsHtml, $html);
		$html = str_replace('#service', ucfirst($this->name), $html);
		$html = str_replace('#version', HESSIAN_PHP_VERSION, $html);
		echo $html;
	}

	/**
	 * Dynamically calls a method in the wrapped object passing parameters from the request
	 * and returns the result. Generates a fault if the method does not exist or cannot be invoked.
	 *  
	 * @param string method Name of the method
	 * @param array params Array of parameters to be passed
	 * @return mixed Returned value from the service or null if fault
	 * @access protected 
	 **/
	public function callMethod($method,$params,$writer){
		try {
			$methodObj = $this->info->getMethod($method);
			if($this->isMethodCallable($methodObj))
				return call_user_func_array(array($this->service,$method),$params);
			else throw new Exception();
		} catch (Exception $e) {
			$writer->setFault('1',"Method $method does not exist in this service");
			return null;
		}
	}
	
	private function isMethodCallable($method){
		$name = $method->getName();
		if($name == '__construct' || $name == '__destruct' || strcasecmp($name,get_class($this->service)) == 0) 
			// always exclude constructors and destructors
			return false;
		if(!$method->isPublic()) // only public methods
			return false;
		if(strpos($name,'_') === 0 && !$this->underscoreInclude) // wheter to include old style private methods
			return false;
		return true;
	}

}

?>