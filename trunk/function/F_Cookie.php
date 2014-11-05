<?php
/**
 *	File: F_Cookie.php
 *  Functionality: Extra Cookie functions
 *  Author: Nic XIE
 *  Date: 2013-3-15
 *  Remark: 
 */

// Clear cookie value
function clearCookie($name, $cookiedomain = ''){
	$cookiedomain .= '.513fdw.com';
	setCookie($name, '', CUR_TIMESTAMP - 3600, '/', $cookiedomain);
}


/**
 * Set search COOKIE
 *	 1: Clear cookie
 *  2: Set cookie 
 */
function setSearchCookie($name, $value, $expire = 3600, $cookiedomain = ''){
	$cookiedomain .= '.513fdw.com';
	clearCookie($name);
	setCookie($name, $value, CUR_TIMESTAMP + $expire, '/', $cookiedomain);
}


/**
 * 添加cookie
 * 
 * @auth 小皓
 * @param string $name
 * @param type $value
 * @param type $expire
 * @param string $cookiedomain
 */
function oSetCookie($name, $value, $expire = 3600, $cookiedomain = ''){
	$cookiedomain .= '.513fdw.com';
	setCookie($name, $value, CUR_TIMESTAMP + $expire, '/', $cookiedomain);
}

/**
 * 获取cookie
 * 
 * @auth 小皓
 * @param string $name
 * @return null
 */
function getCookie($name){
	$value = $_COOKIE[$name];
	if (!isset($value)) {
		return null;
	}

	$value = filter(stripSQLChars(stripHTML(trim($value))));

	return $value;
}
