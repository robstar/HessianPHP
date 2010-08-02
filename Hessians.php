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

define('HESSIAN_PHP_VERSION','1.0.5 RC2');

define('HESSIAN_FAULT',1);
define('HESSIAN_PARSER_ERROR',2);
define('HESSIAN_WRITER_ERROR',3);
define('HESSIAN_HTTP_ERROR',4);

define('HESSIAN_SILENT',1);
define('HESSIAN_EXCEPTION',2);
define('HESSIAN_TRIGGER_NOTICE',3);
define('HESSIAN_TRIGGER_ERROR',4);

/**
 * Main client configuration class. Use it's static methods to configure several options for the connection to a url
 * and mapping of remote types to php types via deserializers.
 * See HessianPHP tests for examples of usage.
 * 
 * @package HessianPHP
 * @author Vegeta
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/
class Hessian_Hessian {

	/**
	 * Returns an object representing information on a remote Hessian url.
	 * Optionally, it can create a default object if needed
	 *  
	 * @param string url Remote url
	 * @param boolean create Shall it create the object?
 	 * @access public 
	 * @static
	 **/
	function &getHessian_HessianProxy($url,$options=false){
		/*$config = &HessianConfig::globalConfig();
		if(!isset($config->proxies[$url])) {
			$proxy = &new Hessian_HessianProxy($url,$options);
			$config->proxies[$url] = &$proxy;
		}
		$proxy = &$config->proxies[$url];
		$proxy->options = $options;
		return $proxy;*/
		$proxy = new Hessian_HessianProxy($url,$options);
		return $proxy;
	}

	/**
	 * Registers a remote method name for a service url. 
	 *  
	 * @param string url Url of the service
	 * @param string name Name of the remote service method 
 	 * @access public 
	 * @static
	 **/
	function remoteMethod($url,$name){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->remoteMethodUrl($url,$name);
	}

	/**
	 * Sets a mapping between a remote object type to a php object type.
	 * It maps the class name received in a service to a local php class to be created
	 * and deserialized by the default object deserializer.
	 * Usefull to match types from different platforms to custrom php classes in a simple way.  
	 *
	 * @param string remoteType Name of the remote Class
	 * @param string phptype Name of the PHP class to create
 	 * @access public 
	 * @static
	 **/
	function mapRemoteType($remoteType,$phptype){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->typeMap->mapRemoteType($remoteType,$phptype);
	}

	function setDateProvider(&$provider){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->dateProvider = &$provider;
	}

	function &getDateProvider(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->dateProvider;
	}

	function errorReporting($level=null){
		$config = &Hessian_HessianConfig::globalConfig();
		if($level!=null)
			$config->errorLog->errorLevel = $level;
		return $config->errorLog->errorLevel;
	}

	function error(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->errorLog->getLastError();
	}

	function getErrorStack(){
		$config = &Hessian_HessianConfig::globalConfig();
		return $config->errorLog->errorStack;
	}

	function isError($obj){
		if( is_a($obj, 'Hessian_HessianError') || is_a($obj, 'HttpError') )
			return true;
		return false;
	}

	function addFilter(&$obj,$name=''){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->addFilter($obj,$name);
	}

	function removeFilter($name){
		$config = &Hessian_HessianConfig::globalConfig();
		$config->removeFilter($name);
	}
}


// static initialization
Hessian_HessianConfig::globalConfig();
// uncomment this line if you want to control error_reporting() yourself
//Hessian::addFilter(new Hessian_PHPErrorReportingFilter());
?>
