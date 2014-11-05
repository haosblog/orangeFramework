<?php
/*
 * File: DB_config.php
 * Functionality: DB config
 * Author: Nic XIE
 * Date: 2012-2-10
 * Remark: if there are more then one DBs in your applicaton, add as below
 	'DB_KEY' => 'DB_NAME',
	 For example: 'DB' => 'test', 'LOG' => 'log',
 	 And 'TYPE' is prepared for PDO !
 */

$Config['DB'] = $DB_Config = array(
	'TYPE' => 'mysql',
	'HOST' => '192.168.1.7',
	'PORT' => 3306,

	'USER' => 'fdw',
	'PSWD' => '456852.com',

	'Default'  => 'fdw',
	'Crawl'    => 'crawl',
	'Log'      => 'log',
	'Wp' 	   => 'wordPress',
);