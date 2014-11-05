<?php
/**
 * File: L_Curl.class.php
 * Functionality: 对CURL进行二次封装，实现对gzip、模拟登陆、cookie保存等功能的封装
 * Author: hao
 * Date: 2014-6-27 17:04:25
 */

class L_Curl {
	
	//页面经过gzip压缩，默认为未压缩
	private $gzip = false;
	
	//cookie文件的保存路径，默认为空，即不开启cookie
	private $cookie = null;
	
	//是否需要登陆，默认不需要，登陆必须开启cookie
	private $needLogin = false;
	
	//登陆所需参数
	private $loginParam = array();
	
	//请求的URL
	private $url = '';
	
	//curl对象
	private $ch;



	/**
	 * 构造函数，生成curl对象，根据传入的url设置即将请求的url
	 * 
	 * @param type $url
	 */
	public function __construct($url = '') {
		Helper::import('Validate');
		
		$this->ch = curl_init();
		
		if(!empty($url) && isUrl($url)){
			$this->url = $url;
		}
	}
	
	
	/**
	 * 设定请求的URL
	 * 
	 * @param type $url
	 */
	public function setUrl($url){
		if(isUrl($url)){
			$this->url = $url;
			return $this;
		}
		
		return false;
	}
	
	
	/**
	 * 设置登陆所需参数
	 * 
	 * @param type $param
	 * @param type $cookieFile
	 */
	public function setLoginInfo($param, $cookieFile = ''){
		$this->needLogin = true;
		
		$this->loginParam = $param;
		
		$this->setCookie($cookieFile);
		
		return $this;
	}
	
	/**
	 * 设置cookie
	 * 
	 * @param type $cookieFile
	 * @return L_Network.
	 */
	public function setCookie($cookieFile = ''){
		if(empty($cookieFile)){
			$this->cookie = TMP_PATH .'/cookie/default';
		} else {
			$this->cookie = TMP_PATH .'/cookie/'. $cookieFile;
		}

		return $this;
	}
	
	
	/**
	 * 登陆账号
	 * 
	 * @param type $loginParam
	 * @param type $cookieFile
	 */
	public function login($loginParam = array(), $cookieFile = ''){
		//如果
		if(!empty($loginParam)){
			
		}
		
		$this->setCookie($cookieFile);
	}
	
	/**
	 * 发送get请求
	 * 
	 * @param type $url
	 */
	public function get($url = ''){
		
	}
	
	
	public function request($url = '', $method = 'get', $data = array()){
		$url = empty($url) ? $this->url : $url;
	}
}