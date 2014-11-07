<?php
/**
 * File: Helper.class.php
 * Functionality: Controller, library, function loader and raiseError
 * Author: Nic XIE
 * Date: 2013-5-8
 * ----------------- DO NOT MODIFY THIS FILE UNLESS YOU FULLY UNDERSTAND ! ------------------
 */

abstract class Helper {

	private static $obj;
	public static $config;
	protected static $version = '1.5';

	/**
	 * Get framework version
	 *
	 * @param null
	 * @return framework version
	 */
	public static function getVersion() {
		return self::$version;
	}


	/**
	 * [getCities 查询已开的分站城市 (热门城市)]
	 * @return [type] [description]
	 */
	public static function getCities(){
		return self::loadModel('City')->getOpenCity();
	}


	/**
	 * Load controller
	 * <br />After loading a controller, the new instance will be added into $obj immediately,
	 * <br />which is used to make sure that the same controller is only loaded once per page !
	 *
	 * @param string controller to be loaded
	 * @return new instance of $c or raiseError on failure !
	 */
	public static function load($c) {
		$controller = 'C_'.ucfirst($c);

		if(self::$obj[$controller] && is_object(self::$obj[$controller])) {
			return self::$obj[$controller];
		}
		
		$file = CTRL_PATH .'/'. $c .'/'. $controller .'.php';
		
		if(defined('SITE_GROUP')){
			if(SITE_GROUP == $c){
				$file = CTRL_PATH .'/'. $controller .'.php';
			}
			
			if(!file_exists($file)){
				$file = APP_PATH .'/controller/'. $c .'/'. $controller .'.php';
				define('NOGROUP', 1);
			}
		}

		if(file_exists($file)){
			require_once $file;
		} else {
			$traceInfo = debug_backtrace();
			$error = 'Controller '.$controller.' NOT FOUND !';
			self::raiseError($traceInfo, $error);
		}

		try{
			self::$obj[$controller] = new $controller();
			return self::$obj[$controller];
		}catch(Exception $error){
			$traceInfo = debug_backtrace();
			$error = 'Load controller '.$controller.' FAILED !';
			self::raiseError($traceInfo, $error);
		}
	}
	
	/**
	 * 加载Component。组件存放于trunk/component下，文件名为Com_ + 组件名。
	 * 2014-7-4 change by 小皓 新增分组功能，调整组件目录结构，现在组件不需要存放于与组件同名的目录了
	 * 
	 * @param string $c	组件名，首字母不需要大写，自动转换
	 * @param type $renew	重新实例化模式。如果传入为true，构建组件时将重新实例化，而不是直接调用原缓存的实例化对象
	 * @return \componment
	 */
	public static function loadComponment($c, $renew = FALSE){
		//如果$c中存在/则启用分组，分组信息存入group中
		$group = '';
		if(strpos($c, '/') !== FALSE){//开启分组
			list($group, $c) = explode('/', $c);
			$c = empty($c) ? 'default' : $c;
			$group .= '/';
		}

		//补全组件名，添加Com前缀以及首字母大写
		$componment = 'Com_'.ucfirst($c);

		//根据组件名和分组，计算哈希值，用于将加载的组件对象存于self::$obj中
		$hash = md5($group . $componment);
		if(self::$obj[$hash] && !$renew){//如果已经实例化过一次组件对象，且并未指定重新实例化，则返回原来实例化的对象
			return self::$obj[$hash];
		}

		$file = APP_PATH .'/componment/'. $group . $componment .'.php';
		
		if(file_exists($file)){//文件存在，加载
			require_once $file;
		} else {//文件不存在，尝试查找同名目录下的文件
			$file = TRUNK_PATH .'/componment/'. $c .'/'. $componment .'.php';

			if(file_exists($file)){//文件存在，加载
				require_once $file;
			} else {
				$traceInfo = debug_backtrace();
				$error = 'Componment '.$componment.' NOT FOUND !';
				self::raiseError($traceInfo, $error);
			}
		}

		try{
			self::$obj[$hash] = new $componment();
			return self::$obj[$hash];
		} catch(Exception $error) {//执行出错，报错
			$traceInfo = debug_backtrace();
			$error = 'Load Componment '.$componment.' FAILED !';
			self::raiseError($traceInfo, $error);
		}
	}
	

	public static function loadClient(){
		if(self::$obj['SDKClient'] && is_object(self::$obj['SDKClient'])) {
			return self::$obj['SDKClient'];
		}

		require_once SDK_PATH . '/Client.php';
		include BASE_PATH . '/plugin/sdks/api/config.php';
		
		$c = new Client();
		$c->appkey = $clientID;
		$c->secretKey = $clientSecret;

		self::$obj['SDKClient'] = $c;
		
		return $c;
	}
	
	
	/**
	 * Load API controller
	 * <br />After loading a controller, the new instance will be added into $obj immediately,
	 * <br />which is used to make sure that the same controller is only loaded once per page !
	 *
	 * @param string controller to be loaded
	 * @return new instance of $c or raiseError on failure !
	 */
	public static function loadAPIController($c) {
		$controller = 'A_'.ucfirst($c);

		if(self::$obj[$controller] && is_object(self::$obj[$controller])) {
			return self::$obj[$controller];
		}

		$file = API_PATH.'/'.$controller.'.php';
		if(file_exists($file)){
			require $file;
		}else{
			eand(ERR_CONTROLLER_NOT_FOUND); 
		}

		try{
			self::$obj[$controller] = new $controller();
			return self::$obj[$controller];
		}catch(Exception $error){
			eand(ERR_LOAD_CONTROLLER_FAIL);
		}
	}

	
	/**
	 * Load model
	 * <br />After loading a model, the new instance will be added into $obj immediately,
	 * <br />which is used to make sure that the same model is only loaded once per page !
	 *
	 * @param string => model to be loaded
	 * @return new instance of $model or raiseError on failure !
	 */
	public static function loadModel($model) {
		$path = '';
		//新增分组功能
		if(strpos($model, '/') !== FALSE){
			list($category, $model) = explode('/', $model);
			$path = '/'. $category;
		}
		$modelClass = 'M_'.ucfirst($model);
		$hash = md5($path . $modelClass);

		if(self::$obj[$hash] && is_object(self::$obj[$hash])) {
			return self::$obj[$hash];
		}

		$file = MODEL_PATH .$path .'/'.$modelClass.'.php';
		if(file_exists($file)) {
			require_once $file;

			try{
				$modelObj = new $modelClass;
			}catch(Exception $error) {
				$traceInfo = debug_backtrace();
				$error = 'Load model '.$modelClass.' FAILED !';
				Helper::raiseError($traceInfo, $error);
			}
		} else {
			$modelObj = new M_Model($model);
		}
		
		self::$obj[$hash] =$modelObj;
		return $modelObj;
	}


	/**
	 * Execute $action in controller $class
	 * <br/> Usage: Helper::dispatch('user', 'index')
	 *
	 * @param string controller to be loaded
	 * @param string action to be executed, default is index
	 * 			so you call just call like this: Helper::dispatch('user');
	 * @return null
	 */
	public static function dispatch($class, $action = 'index') {
		self::load($class)->$action();
	}


	/**
	 * Import function or library
	 *
	 * @param string file to be imported
	 * @return null
	 */
	public static function import($file) {
		$file     = ucfirst($file);
		$library  = 'L_'.$file;
		$l_file   = LIB_PATH.'/'.$library.'.class.php';

		$function = 'F_'.$file;
		$f_file   = FUNC_PATH.'/'.$function.'.php';

		if(file_exists($l_file)){
			require_once $l_file;
		}else if(file_exists($f_file)){
			require_once $f_file;
		}else{
			$traceInfo = debug_backtrace();
			$error = 'Function or Library '.$file.' NOT FOUND !';
			self::raiseError($traceInfo, $error);
		}
	}


	/**
	 *  Create memcached instance
	 */
	public function getMemcacheInstance(){
		if(!self::$obj['L_Memcache']){
			self::import('Memcache');
			self::$obj['L_Memcache'] = new L_Memcache();
		}

		return self::$obj['L_Memcache'];
	}


	/**
	 * Import the SDK class
	 * 
	 * @param string $sdk	SDK file name
	 */
	public static function importSDK($sdk){
		$sdkClass = ucfirst($sdk) .'Request';
		$sdkFile = SDK_PATH .'/'. $sdk .'/'. $sdkClass .'.php';
		//如果不存在Client类，则加载Client.php
		if(!class_exists('Client')){
			require_once SDK_PATH . '/Client.php';
		}
		//检测是否已存在同名类，存在则不引入
		if(!class_exists($sdkClass)){
			if(file_exists($sdkFile)){
				require_once $sdkFile;
			} else {
				$traceInfo = debug_backtrace();
				$error = 'SDK '.$sdk.' NOT FOUND !';
				self::raiseError($traceInfo, $error);
			}
		}
	}


	/**
	 * Load config file
	 * 
	 * <br />Remark: if your config file name is 'Mail_config.php', $config should be 'Mail';
	 */
	public static function loadConfig($config) {
		self::$config[] = $config;
	}
	
	
	/**
	 * 
	 * @param type $config
	 */
	public static function loadConfigData($config){
		
		$file = CONFIG_PATH.'/'. ucfirst($config).'_config.php';
		if(file_exists($file)){
			include $file;
		}
		
		return $Config[$config];
	}
	
	
	/**
	 * Response
	 * 
	 * @param string $format : json, xml, jsonp, string
	 * @param array $data: 
	 * @param boolean $die: die if set to true, default is true
	 */
	public static function response($data, $format = 'json', $die = TRUE) {
		switch($format){
			default:
			case 'json':
				header('Content-type:text/json');
				$data = json_encode($data);
			break;
			
			case 'jsonp':
				$data = $_GET['jsoncallback'] .'('. json_encode($data) .')';
			break;
			
			case 'xml':
				self::import('XML');
				$data = XML_serialize($data);
			break;
			
			case 'string':
			break;
		}

		echo $data;
		
		if($die){
			die;
		}
	}


	/**
	 * Raise error and halt if it is under UAT
	 *
	 * @param string debug back trace info
	 * @param string error to display
	 * @param string error SQL statement
	 * @return null
	 */
	public static function raiseError($traceInfo, $error, $sql = '') {
		$errorMsg = "<h2 style='color:red'>Error occured !</h2>";
		$errorMsg .= '<h3>' . $error . '</h3>';
		if ($sql) {
			$errorMsg .= 'SQL: ' . $sql . '<br /><br />';
		}

		$errorMsg .= 'The following table shows trace info: <table border=1 width=100%>';
		$errorMsg .= "<tr style='text-align:center;color:red;background-color:yellow'>";
		$errorMsg .= '<th>NO</th><th>File</th><th>Line</th><th>Function</th></tr>';

		$i = 1;
		foreach ($traceInfo as $v) {
			$errorMsg .= '<tr height=40>';
			$errorMsg .= '<td align="center">' . $i . '</td>';
			$errorMsg .= '<td>' . $v['file'] . '</td>';
			$errorMsg .= '<td align="center">&nbsp;' . $v['line'] . '</td>';
			$errorMsg .= '<td align="center">&nbsp;' . $v['function'] . '()</td>';
			$errorMsg .= '</tr>';
			$i++;
		}

		$errorMsg .= '</table>';
		$errorMsg .= '<h2>Please check and correct it, then try again ! Good Luck !</h2><hr />';
		unset($traceInfo, $v, $sql, $i);

		if(in_array(ENVIRONMENT, array('DEV', 'LOC'))){
			die($errorMsg);
		}else{
			// PRODUCTION: 500 Error
			header('HTTP/1.1 500 Internal Server Error');

	        $html = '<html>
						<head><title>500 Internal Server Error</title></head>
						<body bgcolor="white">
						<center><h1>500 Internal Server Error</h1></center>
						<hr>
						</body>
					</html>';
	        die($html);
		}
	}

}