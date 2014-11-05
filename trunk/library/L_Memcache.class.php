<?php
/*
 * File: L_Memcache.class.php
 * Functionality: Memcache 类
 * Author: Nic XIE
 * Date: 2014-07-08
 * Remark: 
 	1: DEV 中将不起作用 !
    2: 目前使用有问题, 而且也不确定什么情况下需要使用, 到时分析给方案后再处理!
 */

class L_Memcache {

	// 是否启用 Memcached
	private $status = FALSE;

	// 默认缓存时间
	public $expire = 3600;

	// key
	public $key;

	// value
	public $value;

	// compressed ?
	public $compressed = 0;

	private $memcache = null;

	private $logFile = LOG_PATH .'/memcache.log';

	function __construct(){
		include CONFIG_PATH.'/Memcache_config.php';

		// Add distributed memcached server if Memcached is enabled !
		$this->memcache = new Memcache;

		if($this->status === TRUE){
			foreach($MS_Config as $val){
				$result = $this->memcache->addServer($val['HOST'], $val['PORT']);
				if(!$result){
					file_put_contents($this->logFile, 'Error: Could not connect to Memcached Server '.$val['HOST'].NL, FILE_APPEND);
				}
			}
		}
	}

	// Get version
	public function getVersion(){
		return $this->memcache->getVersion();
	}

	public function key($key){
		$this->key = $key;
		return $this;
	}

	public function val($value){
		$this->value = $value;
		return $this;
	}

	public function compressed($value){
		$this->compressed = $value;
		return $this;
	}

	public function expire($value){
		$this->expire = $value;
		return $this;
	}

	// Get
	public function get(){
		if(ENVIRONMENT == 'DEV'){
			return NULL;
		}else{
			if($this->status === TRUE){
				return $this->memcache->get($this->key);
			}else{
				return 1;
			}
		}
	}

	// Set
	public function set(){
		if(ENVIRONMENT == 'DEV'){
			return 1;
		}else{
			if($this->status === TRUE){
				return $this->memcache->set($this->key, $this->value, $this->compressed, $this->expire);
			}else{
				return 1;
			}
		}
	}

	// Replace
	public function replace(){
		if(ENVIRONMENT == 'DEV'){
			return 1;
		}else{
			if($this->status === TRUE){
				return $this->memcache->replace($this->key, $this->value, $this->compressed, $this->expire);
			}else{
				return 1;
			}
		}
	}

	// Delete
	public function delete(){
		if(ENVIRONMENT == 'DEV'){
			return 1;
		}else{
			if($this->status === TRUE){
				return $this->memcache->delete($this->key);
			}else{
				return 1;
			}
		}
	}

	// Get server status 
	// 0 表示离线, 非 0 在线
	public function getServerStatus($host, $port = 11211){
		return $this->memcache->getServerStatus ($host, $port);
	}

	// Get extended stats 
	public function getExtendedStats($type = ''){
		return $this->memcache->getExtendedStats($type);
	}

	// Flush
	public function flush(){
		if($this->status === TRUE){
			return $this->memcache->flush();
		}else{
			return 1;
		}
	}

}