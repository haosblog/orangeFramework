<?php
/**
 * File: orange.php
 * Functionality: 框架入口文件
 * Author: hao
 * Date: 2014-11-6 11:50:21
 */


// Trunk path: TRUNK_PATH
define('TRUNK_PATH', dirname(__FILE__));

define('CONFIG_PATH', TRUNK_PATH.'/config');	// 配置文件所属目录，TODO config机制要修改，本常量计划弃用
define('FUNC_PATH', TRUNK_PATH.'/function');	// 公用函数所属目录
define('LIB_PATH', TRUNK_PATH.'/library');		// 类库所在目录
define('MODEL_PATH', APP_PATH .'/model');		// 模型文件所在目录，存放于应用目录中
define('CORE_PATH', TRUNK_PATH.'/core');		// 系统核心所在目录
define('SDK_PATH', APP_PATH.'/api/sdk');		// SDK path: SDK_PATH，TODO 已弃用

// Current timestamp, datetime, date
date_default_timezone_set('Asia/Chongqing');
define('CUR_TIMESTAMP', time());
define('CUR_DATE', date('Y-m-d', CUR_TIMESTAMP));
define('CUR_DATETIME', date('Y-m-d H:i:s', CUR_TIMESTAMP));

require CORE_PATH .'/Orange.class.php';

Orange::run();