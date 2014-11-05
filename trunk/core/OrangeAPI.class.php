<?php
/**
  * File: OrangeAPI.class.php
  * Functionality: Orange API Class
  * Author: Nic XIE
  * Date: 2012-2-11
  * QQ: 381345509
  * Email: xwmhmily@126.com
  * Remark:
  * --------------- DO NOT MODIFY THIS FILE UNLESS YOU FULLY UNDERSTAND ! ----------------
  */

define('API_MODE', true);
define('ERR_CONTROLLER_NOT_FOUND', 101);
define('ERR_LOAD_CONTROLLER_FAIL', 102);
define('ERR_FUNCTION_NOT_FOUND', 103);
define('ERR_PARAMETER_MISSING', 104);
define('ERR_UNKNOWN', 110);
define('ERR_SIGN', 120);
define('ERR_TIMEOUT', 121);

abstract class Orange {
	
	/**
	 * Init and check runtime environment
	 * <br />1: Load config file
	 * <br />2: check PDO and MySQLi support
	 * <br />Remark: This framework support PDO and MySQLi extension, either is OK !
			If none of them is enabled, raiseError and application halt !
	 */
	private static function init($configFile){
		/**
		 * Check PHP_VERSION
		 */
		if(PHP_VERSION < '5.2'){
			die('<h2>PHP Version must be equal or greater then 5.2!</h2>');
		}
		
		if(file_exists($configFile)){
			require $configFile;
		}else{
			die('<h2>Config file NOT found !</h2>');
		}
		
		/**
		 * if PDO is enabled, we prefer PDO to MySQLi, else use MySQLi instand !
		 */
		if(class_exists('pdo')){
			require CORE_PATH.'/M_Model.pdo.php';
		}else if(class_exists('mysqli')){
			require CORE_PATH.'/M_Model.mysqli.php';
		}else{
			$error = '<h3>This framework requires PDO or MySQLi support !</h3>';
			Helper::raiseError(debug_backtrace(), $error);
		}
	}
	
	
	/**
	 * Visit REST API
	 *
	 * @param string => config file to load
	 */
	public static function run($configFile = ''){
		self::init($configFile);
		include CORE_PATH.'/A_Control.php';
		
		$method = $_GET['method'];
		list($parent, $child, $function) = explode('.', $method);
		
		$extend = true;
		if(!$function){
			$function = $child;
			unset($child);
			$extend = false;
		}
		
		define('API_PATH', BASE_PATH.'/api');
		
		// Load API controller
		$m = null; 
		$parent = ucfirst($parent);
		if($extend){
			$child = ucfirst($child);
			Helper::loadAPIController($parent);
			$m = Helper::loadAPIController($child);
		}else{
			$m = Helper::loadAPIController($parent);
		}
		
		// Is specified action one of the class methods ? NO ? die
		if(!$function || !in_array($function, get_class_methods($m))){
			eand(ERR_FUNCTION_NOT_FOUND);
		}
		
		$format = 'json';
		$fmt = getParam('format');
		if($fmt){
			$format = $fmt;
		}
		
		$data = $m->$function();
		Helper::response($data, $format);
	}

}

?>