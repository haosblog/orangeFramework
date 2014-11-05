<?php
/**
 * File: Orange.class.php
 * Functionality: Orange Class
 * Author: Nic XIE
 * Date: 2013-2-11
 * QQ: 381345509
 * Email: xwmhmily@126.com
 * Remark: Any suggestions or improvement is welcome !
 * ------------ DO NOT MODIFY THIS FILE UNLESS YOU FULLY UNDERSTAND ! -------------------
 */

abstract class Orange {
	
	/*
	 * Init and check runtime environment
	 * <br />1: Load config file
	 * <br />2: check PDO and MySQLi support
	 * <br />Remark: This framework support PDO and MySQLi extension, either is OK !
			If none of them is enabled, raiseError and application halt !
	 */
	private static function init($configFile) {
		/**
		 * Check PHP_VERSION
		 */
		if(PHP_VERSION < '5.2'){
			die('<h2>PHP Version must be greater or equal then 5.2!</h2>');
		}
		// 注册AUTOLOAD方法
//		spl_autoload_register('Orange::autoload');
		
		if(file_exists($configFile)){
			require $configFile;
		}else{
			die('<h2>Config file NOT found !</h2>');
		}
		
		require CORE_PATH.'/M_Model.class.php';
		require TRUNK_PATH.'/interface/database.interface.php';
		require TRUNK_PATH.'/driver/db/DB_'. DB_DRIVER .'.class.php';
		require CORE_PATH.'/DB.class.php';
		
		DB::init();
		
		/**
		 * PDO extension is required
		 */
//		if(class_exists('pdo')){
//			require CORE_PATH.'/M_Model.pdo.php';
//		}else{
//			$error = '<h3>This framework requires PDO support !</h3>';
//			Helper::raiseError(debug_backtrace(), $error);
//		}
	}


	/**
	 * Run application
	 *
	 * @param string => config file to load
	 * @return null
	 */
	public static function run($configFile = '') {
		self::init($configFile);
		
		Helper::import('Rounder');
		$rounderObj = new L_Rounder();
		
		$uri = $rounderObj->run();
		if(!$uri){
			$uri = $_SERVER['REQUEST_URI'];
		}
		
		// Parse URL to extract controller and action
		$url = "http://".$_SERVER['HTTP_HOST'] . $uri;
		$urlInfo = parse_url($url);
			
		// controller group ?
		include BASE_PATH.'/trunk/config/Sitegroup_config.php';
		$prefixArr = array_keys($Config['sitegroup']);
		
		// Get host prefix
		$host = $_SERVER['HTTP_HOST'];
		$hostInfo = explode('.', $host);
		
		// Strip 'dev', 'test', 'www'
		$search = array('dev', 'test', 'www', 'loc');
		$prefix = str_ireplace($search, '', $hostInfo[0]);
		
		// Visit site group [agent,proxy,parnter,user], specific SITE_GROUP, also the default controller
		if(in_array($prefix, $prefixArr)){
			$siteGroup = $Config['sitegroup'][$prefix];
		} else {
			$urlInfo['path'] = ltrim($urlInfo['path'], '/');			
			$router = explode('/', $urlInfo['path']);
			$controller = $router[0];
			
			// URL contains one of the site groups ? 
			$siteGroups = array_values($Config['sitegroup']);
			$siteGroup = 0;
			if(in_array($controller, $siteGroups)){
				$siteGroup = $controller;
			}
		}
		
		if($siteGroup){
			define('SITE_GROUP', $siteGroup);
		}

		$JS_PATH = '/view/js';
		$IMG_PATH = '/view/images';
		$CSS_PATH = '/view/css';
		$CTRL_PATH = BASE_PATH;
		if($urlInfo['path'] === '/' || $urlInfo['path'] === '/index.php'){
			$controller = $action = 'index';
			if(defined('SITE_GROUP')){
				$controller = SITE_GROUP;
				$CTRL_PATH .= '/'. SITE_GROUP;
			}
		}else{
			//URI不为空的时候根据URI和SITE_GROUP配置生成controller和action
			$urlInfo['path'] = ltrim($urlInfo['path'], '/');
			$path = explode('&', $urlInfo['path']);
			$router = explode('/', $path[0]);
			
			$controller = $router[0];
			
			if(defined('SITE_GROUP')){
				$controller = SITE_GROUP;
				if(empty($prefix) || $prefix == 'www' || $router[0] == $controller){
					$action = isset($router[1]) ? $router[1] : 'index';
				} else {
					$action = isset($router[0]) ? $router[0] : '';
				}
				$CTRL_PATH .= '/'. SITE_GROUP;
			} else {
				// Admin mode:  /admin, /admin/index/,  /admin/user/ ...
				if($controller == 'admin'){
					$controller = isset($router[1]) ? $router[1] : '';
					$action = isset($router[2]) ? $router[2] : '';
					
					define('ADMIN_MODE', 1);
					define('ADMIN_PATH', BASE_PATH.'/admin');
					$JS_PATH = '/admin/view/js';
					$IMG_PATH = '/admin/view/images';
					$CSS_PATH = '/admin/view/css';
					$CTRL_PATH .= '/admin';
				} else {
					$controller = isset($router[0]) ? $router[0] : '';
					$action = isset($router[1]) ? $router[1] : '';
				}
			}

			if(!$controller){
				$controller = 'index';
			}
		}
		// JS_PATH
		define('JS_PATH', $JS_PATH);

		// IMG_PATH
		define('IMG_PATH', $IMG_PATH);

		// CSS_PATH
		define('CSS_PATH', $CSS_PATH);
		define('CTRL_PATH', $CTRL_PATH);

		// Load some necessary configs here
		self::loadWebsiteConfig();

		$controllerClass = 'C_'.ucfirst($controller);
		
		if(defined('SITE_GROUP')){
			/**
			  * 获取控制器与方法名
			  * 比如proxy/index与pronxy/login
			  *  proxy控制器中存在index方法，则直接调用proxy控制器的index方法
			  *  proxy控制器中不存在login方法，则调用proxy分组下的login控制器的index方法
			  */
			$controllerFile = CTRL_PATH .'/'. $controllerClass. '.php';
			
			// action 是在主控制器 C_Member 还是分控制器 /member/C_Login
			$actionUnavailable = false;

			//判断与分组同名的控制器是否存在, 如果不存在, 说明应该调用分组名下级作为控制器
			if(!file_exists($controllerFile)){
				$actionUnavailable = true;
			} else if($action){
			
				require_once $controllerFile;
				
				//判断与分组名同名的控制器下是否存在$action方法，不存在则说明应以下级作为控制器
				if(!in_array($action, get_class_methods($controllerClass))){
					$actionUnavailable = true;
				}
			}
			
			if($actionUnavailable){
				if(empty($prefix) || $prefix == 'www' || $router[0] == SITE_GROUP){
					$controller = isset($router[1]) ? $router[1] : '';
					$action = isset($router[2]) ? $router[2] : 'index';
				} else {
					$controller = isset($router[0]) ? $router[0] : '';
					$action = isset($router[1]) ? $router[1] : 'index';
				}
			}
		}

		$controllerObj = Helper::load($controller);
	
		// Is specified action one of the class methods ? NO ? make it 'index'
		if(!$action || !method_exists($controllerObj, $action)){
			$action = 'index';
		}
		
		//echo '<br />__CONTROLLER__: '.$controller.'  __ ACTION__: '.$action.'<br />'; 
		Helper::dispatch($controller, $action);
	}
	
	
	/*
	 * 加载一些必须的配置
	 * @TODO: 根据IP 定位城市
	 * @remark: 
			1: 根据二级域名读取默认的城市ID并设置默认的区域参数
			2: 注意: 二级域名不能有相同的, 比如 广州与赣州, 都是 GZ, 必须有一个是不一样的!!!
			3: 需要 Nginx 将 513fdw.com 转向至 www.513fdw.com, 否则直接输入 513fdw.com 将取不到城市 ID
	 */
	public static function loadWebsiteConfig() {
		$serverName = explode('.', $_SERVER['HTTP_HOST']);
		$prefix = strtolower($serverName[0]);
	
		$find = array('www', 'test', 'dev');
		$prefix = str_ireplace($find, '', $prefix);
		
		if($prefix){
			$field = 'cityID, city, provinceID, py';
			$where = '`py` = "'.$prefix.'" AND `isOpen` = 1';
			$areaArr = Helper::loadModel('City')->Field($field)->Where($where)->SelectOne();
			
			$city = stripAreaKeyWord($areaArr['city']);
			$cityID = $areaArr['cityID'];
			
			$provinceID = $areaArr['provinceID'];
			$province = Helper::loadModel('Province')->getProvinceNameById($provinceID);
			
			// 选择第一个区域为默认区域
			$field = 'region, regionID';
			$where = '`cityID` = "'.$cityID.'"';
			$regionArr = Helper::loadModel('Region')->Field($field)->Where($where)->SelectOne();
			
			$region = $regionArr['region'];
			$regionID = $regionArr['regionID'];
			
			// 选择第一个板块为默认区域
			$field = 'district, districtID';
			$where = '`regionID` = "'.$regionID.'"';
			$districtArr = Helper::loadModel('District')->Field($field)->Where($where)->SelectOne();
			
			$district = $districtArr['district'];
			$districtID = $districtArr['districtID'];
		}
		
		if(empty($city)){
			// 默认为广东, 广州, 天河, 珠江新城
			$province = '广东';
			$provinceID = 440000;
			
			$city = '广州';
			$cityID = 440100;
			
			$region = '天河';
			$regionID =  440106;
			
			$district = '珠江新城';
			$districtID = 93;
		}
		
		define('PROVINCE', $province);
		define('SITE_PROVINCE', $provinceID);

		define('CITY', $city);
		define('SITE_CITY', $cityID);
		
		define('REGION', $region);
		define('SITE_REGION', $regionID);

		define('DISTRICT', $district);
		define('SITE_DISTRICT', $districtID);
		
		//echo PROVINCE.' _ '.SITE_PROVINCE.' _ '.CITY.'__'.SITE_CITY.'_'.REGION.' _ '.SITE_REGION.' _ '.DISTRICT.'__'.SITE_DISTRICT;
		
		if(ENVIRONMENT != 'WWW') {
			$PREFIX = ENVIRONMENT.$areaArr['py'];
		}else{
			if($areaArr['py']){
				$PREFIX = $areaArr['py'];
			}else{
				$PREFIX = 'www';
			}
		}

		// SERVER_DOMAIN
		define('SERVER_DOMAIN', 'http://'.$PREFIX.'.513fdw.com');

		// 如果是 AJAX 请求, 清空 REFERER
		if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
			$_SERVER["HTTP_REFERER"] = '';
		}
	}

	/**
	 * 类库自动加载
	 * @param string $class 对象类名
	 * @return void
	 */
	static function autoload($class){
		
	}
}