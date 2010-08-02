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
 * Represents a date time value. Works with ISO Hessian_DateTime format like
 *
 * "YYYY-MM-DD HH:mm:ss"
 * 
 * @package default
 * @author Manolo G�mez
 * @copyright Copyright (c) 2005
 * @version 1.0
 * @access public
 **/

class Hessian_DateTime{
	var $day;
	var $month;
	var $year;
	var $hour;
	var $minute;
	var $second;
	var $_timestamp;
	var $weekDay;

	/**
	 * Constructor
	 *  
	 * @param mixed date object, string or timestamp to use as a base for the object
	 **/
	function __construct($date='now'){
		//echo $date;
		if(is_string($date)) {
			$this->setTimestamp(strtotime($date));
			/*list($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second) =
                   sscanf($date, "%04u-%02u-%02u %02u:%02u:%02u");
			$this->sync();*/
        }
		elseif(is_numeric($date)) { // 18000 is the ts for 1970-01-01
			//$str = date("Y-m-d H:i:s", $date);
			//$this->sync($str);
			$this->setTimestamp($date);
		}
		elseif(is_object($date) && is_a($date,'Hessian_DateTime'))
			$this->setTimestamp($date->_timestamp);
		else
			trigger_error('Unsuported date/time datatype conversion:'.gettype($date));
	}


	/**
	 * Uses a timestamp integer to build this object's fields
	 * 
	 * @param integer ts timestamp to decode
	 **/
	function setTimestamp($ts){
		if($ts==-1) {
			return trigger_error('FATAL: Incorrect timestamp',E_USER_ERROR);
		}
		$this->_timestamp = $ts;
		$this->year = date('Y',$ts);
		$this->month = date('m',$ts);
		$this->day = date('d',$ts);
		$this->hour = date('H',$ts);
		$this->minute = date('i',$ts);
		$this->second = date('s',$ts);
		$this->weekDay = date('w',$ts);
		//$this->_timestamp = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year,0);
	}
	

	/**
	 * Rebuilds object's internal timestamp.
	 *
	 * A call to this function is needed when adding or substracting days or minutes to the date
	 **/
	function sync(){
		$this->_timestamp = mktime($this->hour,$this->minute,$this->second,$this->month,$this->day,$this->year,0);
		$this->setTimestamp($this->_timestamp);
	}

	function &asTime(){
		return new Hessian_DateTime($this->getTime());
	}

	function &asDate() {
		return new Hessian_DateTime($this->getDate());
	}
	
	/**
	 * @return string Time string
	 **/
	function getTime(){
		return $this->hour.':'.$this->minute.':'.$this->second;
	}

	/**
	 * @return string Date string in ISO format
	 **/
	function getDate(){
		return $this->year.'-'.$this->month.'-'.$this->day;
	}

	function getTimestamp(){
		return $this->_timestamp;
	}

	function getWeekDay(){
		return $this->weekDay;
	}

	function getDayLight(){
		return $this->dayLightSave;
	}

	function getLocalWeekDay(){
		return date('l',$this->_timestamp);
	}

	function daysInMonth(){
		return date('t',$this->_timestamp);
	}

	function isBefore($time){
		if(Hessian_DateTime::compare($this,$time) == -1)
			return true;
		return false;
	}

	function isAfter($time){
		if(Hessian_DateTime::compare($this,$time) == 1)
			return true;
		return false;
	}

	function equals($time){
		if(Hessian_DateTime::compare($this,$time) == 0)
			return true;
		return false;
	}

	function getHessian_DateTime(){
		return $this->getDate().' '.$this->getTime();
	}

	function strftime($format){
		return strftime($format,$this->_timestamp);
	}

	function gmstrftime($format){
		return gmstrftime($format,$this->_timestamp);
	}

	function daysDiff(&$other){
		$diff = $this->dateDiff($other);
		return $diff['days'];
	}


	/**
	 * Calculates the difference between two Hessian_DateTime objects.
	 * Returns an associative array containing a timespan expressed in days, hours, minutes and seconds.
	 *  
	 * @param Hessian_DateTime other Object to compare with
	 * @return array array with difference information 
	 **/
	function dateDiff(&$other){
		$laterDate = max($other->_timestamp,$this->_timestamp);
		$earlierDate = min($other->_timestamp,$this->_timestamp);

		// OJO, QUE TAMBIEN RESTA CON HORAS, MINUTOS Y SEGUNDOS, fuente: foros php.net
		//returns an array of numeric values representing days, hours, minutes & seconds respectively
		$ret=array('days'=>0,'hours'=>0,'minutes'=>0,'seconds'=>0);

		$totalsec = $laterDate - $earlierDate;
		if ($totalsec >= 86400) {
			$ret['days'] = floor($totalsec/86400);
			$totalsec = $totalsec % 86400;
		}
		if ($totalsec >= 3600) {
			$ret['hours'] = floor($totalsec/3600);
			$totalsec = $totalsec % 3600;
		}
		if ($totalsec >= 60) {
			$ret['minutes'] = floor($totalsec/60);
		}
		$ret['seconds'] = $totalsec % 60;
		return $ret;
	}

	/**
	 * Compares two date time values (t1 and t2). 
	 * Can work with Hessian_DateTime objects, string values and integer timestamps
	 *
	 * Possible return values are:
	 * -1: t1 < t2
	 *  0: t1 = t2 
	 *  1: t1 > t2
	 *   
	 * @param mixed time1 First time value 
	 * @param mixed time2 Second time value
	 * @return int Result of the comparison
	 **/
	function compare($time1,$time2){
		// delegamos encontrar el tipo de lo que se este pasando a otra funci�n, 
		// una especie de overload
		$ts1 = Hessian_DateTime::findTimestamp($time1);
		$ts2 = Hessian_DateTime::findTimestamp($time2);

		if($ts1<$ts2)
			return -1; // 1 menor que 2
		if($ts1>$ts2)
			return 1; // 1 mayor que 2
		if($ts1===$ts2)
			return 0; // 1 igual a 2
	}

	function monthName($value=null){
		//int mktime ( int hour , int minute , int second , int month , int day , int year , int daylight_saving)
		if($value==null){
			if($this)
				$ts = $this->_timestamp;
			else {
				return trigger_error('Missing parameter in static call to monthName',E_USER_WARNING);
			}
		}elseif(is_object($value) && is_a($value,'Hessian_DateTime'))
			$ts = $value->_timestamp;
		else {
			$ts = mktime(0, 0, 0, $value);
		}
		if($ts==-1)
			return trigger_error('Incorrect timestamp',E_USER_WARNING);
		return strftime("%B",$ts);
	}

	/**
	 * Returns the timestamp contained in $dateObj
	 * Can work with Hessian_DateTime objects, string values and integer timestamps
	 *  
	 * @param mixed dateObj variable to search
	 * @return integer timestamp
	 **/

	function findTimestamp($dateObj){
		if(is_object($dateObj) && is_a($dateObj,'Hessian_DateTime'))
			return $dateObj->getTimestamp();
		if(is_int($dateObj))
			return $dateObj;
		if(is_string($dateObj))
			return strtotime($dateObj);
		return trigger_error('FATAL: Cannot find timestamp of '.gettype($dateObj),E_USER_ERROR);
	}

	function __call($method,$params){
		$reg = "/(add|sub|substract)(day|minute|second|hour|year|month)s/";
		$method = strtolower($method);
		if(preg_match($reg, $method, $matches)){
			$op = $matches[1];
			$part = strtolower($matches[2]);
			$num=1;
			if(!empty($params[0])){
				$num = $params[0];
			}
			return $this->__dateOperation($op,$part,$num);
		}
		return false;
	}

	function __dateOperation($op,$part,$num){
		$parts = array('day','minute','second','hour','year','month');
		if(!in_array($part,$parts)) {
			return trigger_error("operation: '$part' is not a part of Hessian_DateTime",E_USER_WARNING);
		}
		switch($op){
			case 'add':
			case '+': $this->$part += $num; break;
			case 'sub':
			case 'substract':
			case '-': $this->$part -= $num; break;
		}
		$this->sync();
		return true;
	}
	
	function add($part,$num=1){
		return $this->__dateOperation('+',$part,$num);
	}

	function sub($part,$num=1){
		return $this->__dateOperation('-',$part,$num);
	}

	function substract($part,$num=1){
		return $this->__dateOperation('-',$part,$num);
	}

	function __toString(){
		return $this->getHessian_DateTime();
	}

}

if (function_exists('overload') && phpversion() < 5) {
   overload('Hessian_DateTime');
}

/*
// Tests

$date1 = new Hessian_DateTime('2004-09-06 8:00:00');
$date2 = new Hessian_DateTime('2004-09-06 9:00:00');
$date3 = new Hessian_DateTime('2004-09-06 8:00:00');


if($date1->isBefore($date2))
	echo 'si<br>';
if($date2->isAfter($date1))
	echo 'si<br>';
if($date1->equals($date3))
	echo 'si<br>';

$now = new Hessian_DateTime();
echo $now->monthName();
echo $now->monthName($date1);
echo Hessian_DateTime::monthName($now);
echo '<br>';
echo $now->getHessian_DateTime();
$now->addYears(5);
echo '<br>';
echo $now->getHessian_DateTime();

$now->add('minute',5);
echo '<br>';
echo $now->getHessian_DateTime();

$now->sub('hour',12);
$now->substract('year',3);
echo '<br>';
echo $now->getHessian_DateTime();
*/
?>
