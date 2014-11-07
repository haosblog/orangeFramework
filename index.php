<?php
// Set charset !
header('content-Type:text/html;charset=utf-8;');

// Path to the system folder
define('APP_NAME', '513fdw');
define('WEBSITE_NAME', '房道网');
define('DS', str_replace("\\", "/", DIRECTORY_SEPARATOR));

define('BASE_PATH', str_replace("\\", "/", dirname(__FILE__)));
define('APP_PATH', BASE_PATH .'/application');
define('PARENT_DIR', dirname(BASE_PATH));

define('TMP_PATH', BASE_PATH.'/tmp');				// 临时文件存储目录
define('TEMPLATE_DIR', BASE_PATH.'/view');			// 模板保存目录，TODO 位置将迁移
define('HTML_PATH', BASE_PATH.'/html');				// 伪静态页面保存目录
define('UPLOAD_PATH', PARENT_DIR .'/upload');		// 图片上传保存目录
define('CACHE_PATH', PARENT_DIR .'/cache_fdw');		// 系统缓存目录
define('RUNTIME_PATH', PARENT_DIR .'/runtime');		// 运行缓存的目录
define('CMP_PATH', PARENT_DIR .'/views_c_fdw');		// Smarty的编译目录
define('RESOURCE_PATH', BASE_PATH .'/resource');	// 资源存储

$LOG_PATH = '/var/log/www';



// 定义手机验证码过期时间 10分钟
define('CAPT_EXPIRED_TIME', 600);

//邮件激活码过期时间  24小时
define('CAPTCHA_EXPIRE_IN', 86400);

// Table prefix: TB_PREFIX
define('TB_PREFIX', 'gf_');

define('SIGNATURE', '【房道网】');

// 经纬度放大的倍数: 1 亿
define('LNG_ZOOM_RATE', 100000000);
define('LAT_ZOOM_RATE', 100000000);

// 400 号码
define('SERVICE_PHONE', '4000513593');
define('PHONE_PREFIX', '4000-513-593');

define('OPP_SUCCESS', '操作成功!');
define('OPP_FAILURE', '操作失败, 请联系管理员或客服!');

require BASE_PATH.'/environment.php';	//引用部署文件，用于区分当前运行环境
// 根据部署文件的定义，设置不同的参数
switch(ENVIRONMENT) {
	case 'DEV':// 开发机模式
		error_reporting(E_ALL ^E_NOTICE);
		ini_set('display_errors', 'on');
        
        $YPAIYUN = 'devimg'; //又拍云空间名
        $YPAIYUN_USER = 'fdw2014';
        $YPAIYUN_PASS = 'fdw123456';
        $YPAIYUN_DOMAIN  = 'http://devimg.b0.upaiyun.com';
        
		$IMG_DOMAIN    = 'http://devImg.513fdw.com';//如果以后用到其他尺寸，用这种方式加：'<{$smarty.const.IMG_DOMAIN}>/viewimage/widthxheight/'
		$API_DOMAIN    = 'http://devApi.513fdw.com';
		$USER_DOMAIN   = 'http://devuser.513fdw.com';
		$WX_DOMAIN     = 'http://devWx.513fdw.com';
		$STATIC_DOMAIN = 'http://devStatic.513fdw.com';
	break;

	case 'TEST':// 过渡机模式
		error_reporting(E_ALL ^E_NOTICE);
		$logFile = '/var/log/www/php/'.CUR_DATE.'.log';
		if(!file_exists($logFile)){
			touch($logFile);
		}
		
		ini_set('display_errors', 'off');
		ini_set('log_errors', 'on');
		ini_set('error_log', $logFile);
        
        $YPAIYUN = 'uatimg'; //又拍云空间名
        $YPAIYUN_USER = 'fdw2';
        $YPAIYUN_PASS = 'fdw123456';
        $YPAIYUN_DOMAIN  = 'http://uatimg.b0.upaiyun.com';
        
		$IMG_DOMAIN    = 'http://testImg.513fdw.com';
		$API_DOMAIN    = 'http://testApi.513fdw.com';
		$USER_DOMAIN   = 'http://testUser.513fdw.com';
		$STATIC_DOMAIN = 'http://testStatic.513fdw.com';
		$WX_DOMAIN     = 'http://testWx.513fdw.com';
	break;

	case 'WWW':// 生产机模式
		error_reporting(E_ALL ^E_NOTICE);
		$logFile = '/var/log/www/php/'.CUR_DATE.'.log';
		if(!file_exists($logFile)){
			touch($logFile);
		}

		ini_set('display_errors', 'off');
		ini_set('log_errors', 'on');
		ini_set('error_log', $logFile);
        
        $YPAIYUN = 'officialimg'; //又拍云空间名
        $YPAIYUN_USER = 'fdw3';
        $YPAIYUN_PASS = 'fdw123456';
        $YPAIYUN_DOMAIN  = 'http://officialimg.b0.upaiyun.com';
        
		$IMG_DOMAIN    = 'http://img.513fdw.com';
		$API_DOMAIN    = 'http://api.513fdw.com';
		$USER_DOMAIN   = 'http://user.513fdw.com';
		$WX_DOMAIN     = 'http://wx.513fdw.com';
		$STATIC_DOMAIN = 'http://static.513fdw.com';
	break;

	case 'LOC':// 本地模式
		error_reporting(E_ALL ^E_NOTICE);
		ini_set('display_errors', 'on');
        
        $YPAIYUN = 'devimg'; //又拍云空间名
        $YPAIYUN_USER = 'fdw2014';
        $YPAIYUN_PASS = 'fdw123456';
        $YPAIYUN_DOMAIN  = 'http://devimg.b0.upaiyun.com';
        
		$IMG_DOMAIN    = 'http://devImg.513fdw.com';
		$API_DOMAIN    = 'http://locApi.513fdw.com';
		$USER_DOMAIN   = 'http://locuser.513fdw.com';
		$WX_DOMAIN     = 'http://locWx.513fdw.com';
		$STATIC_DOMAIN = 'http://locStatic.513fdw.com';
		
		$LOG_PATH = dirname(BASE_PATH) .'/log';
	break;
}

// IMG_DOMAIN
define('YPAIYUN',$YPAIYUN);
define('YPAIYUN_USER',$YPAIYUN_USER);
define('YPAIYUN_PASS',$YPAIYUN_PASS);
define('YPAIYUN_DOMAIN',$YPAIYUN_DOMAIN);

define('IMG_DOMAIN', $IMG_DOMAIN);

define('LOG_PATH', $LOG_PATH);				// 日志文件存储目录，本地运行的话存放于网站目录上层的log目录，而生产环境则存于/var/log中
define('API_DOMAIN', $API_DOMAIN);			// API域名，TODO 未来弃用
define('WX_DOMAIN', $WX_DOMAIN);			// 微信APP域名
define('STATIC_DOMAIN', $STATIC_DOMAIN);	// 静态文件域名
define('USER_DOMAIN', $USER_DOMAIN);		// 用户中心域名

define('STATIC_SUFFIX', '.html');

//默认使用PDO作为数据库驱动
define('DB_DRIVER', 'PDO');

// 引入入口文件，启动程序
require BASE_PATH .'/trunk/orange.php';