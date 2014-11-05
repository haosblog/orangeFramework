<?php
/**
 * File: B_Router.php
 * Functionality: 
 * Author: hao
 * Date: 2014-10-20 10:48:34
 */

class B_Router {

	private $rouder = array();
	private $_dynamicData = array();

	
	public function run(){
        // 优先检测是否存在PATH_INFO
        $uri = trim($_SERVER['REQUEST_URI'],'/');
		$routes = Helper::loadConfigData('rounder');
		if(empty($uri) || !is_array($routes)){ return FALSE;}

		foreach ($routes as $rule => $route){
			$ruleArr = strpos($rule, '/') !== FALSE ? explode('/', $rule) : array($rule);
			$uriArr = strpos($uri, '/') !== FALSE ? explode('/', $uri) : array($uri);
			
			if(!$this->_checkRule($ruleArr, $uriArr)){//检测当前URL是否符合路由规则，如果不符合则跳过
				continue;
			}
			//符合条件，则将动态获取的参数写入GET变量，并返回路由的指向值
			if($this->_dynamicData){
				$_GET = array_merge($this->_dynamicData, $_GET);
			}
			
			return $route;
		}
		
		return FALSE;
	}
	
	/**
	 * 检测当前URL是否符合路由规则的定义，传入两个数组，分别为URL或
	 * 
	 * @param type $ruleArr		路由规则以/切割后的数组
	 * @param type $uriArr		URL以/切割后的数组
	 * @return boolean
	 */
	private function _checkRule($ruleArr, $uriArr){
		// 循环路由规则的每一项，与URL相对比
		foreach($ruleArr as $key => $value){
			if(strpos($value, ':') === FALSE){// 规则中不存在冒号，则使用静态匹配
				if($value != $uriArr[$key]){// 静态匹配URL的结构必须与路由规则一致
					return FALSE;
				}
			} else {// 使用动态规则进行匹配
				if(!$this->_checkDynamicRule($value, $uriArr[$key])){
					return FALSE;
				}
			}
		}
		
		return TRUE;
	}
	
	private function _checkDynamicRule($rule, $uri){
		if(strpos($rule, '(')){// 路由规则中存在括号，则将括号中的内容剔除再给数据赋值
			$arr = array();
			
			preg_match_all("/\((.*?)\)/is", $rule, $arr);
			$argName = $rule;
			foreach($arr[1] as $key => $value){
				if(strpos($uri, $value) === FALSE){
					return FALSE;
				}
				
				//将URL中固定部分剔除，剩余的就是路由所定义参数
				$uri = str_replace($value, '', $uri);
				$argName = str_replace($arr[0][$key], '', $argName);
			}
		}

		$argName = str_replace(':', '', $argName);

		if(!$argName){
			return FALSE;
		}
		$this->_dynamicData[$argName] = $uri;
		return TRUE;
	}

}
