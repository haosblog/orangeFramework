<?php

/**
 * File: DB_PDO.class.php
 * Functionality: 系统底层PDO数据库驱动类
 * Author: hao
 * Date: 2014-9-5 15:34:17
 */
class DB_PDO extends PDO{
	
	public	$pre;
	private $sql = '';
	private $errorMsg = '';
	public	$result;

	// success code of PDO
	private $successCode = '00000';

	// SQL log file: Log SQL error for debug if not under DEV
	private $logFile = '';


	/**
	 * 构造方法
	 * @global type $sysconfig
	 * @param type $config 设置数据库链接信息，如果为空，则调用sysconfig
	 */
	public function __construct(){
		$this->logFile = LOG_PATH .'/sql/'.CUR_DATE.'.log';
		if(!file_exists($this->logFile) && !in_array(ENVIRONMENT , array('DEV', 'LOC'))){
			touch($this->logFile);
		}
		
		$config = Helper::loadConfigData('DB');
//		print_r($config);
		
		$db = $config['Default'];
		$driver = $config['TYPE'];
		$host   = $config['HOST'];
		$port   = $config['PORT'];
		$user   = $config['USER'];
		$pswd   = $config['PSWD'];
		
		$this->pre = TB_PREFIX;

		if(!$port){
			$port = 3306;
		}

		$dsn = $driver.':host='.$host.';port='.$port.';dbname='.$db;

		try{
			parent::__construct($dsn, $user, $pswd);
			parent::query('SET NAMES utf8');
		}catch(PDOException $e){
			$this->errorMsg = $e->getMessage();
			self::error();
		}
	}

	public function close() {
		;
	}

	/**
	 * 出错，输出错误信息
	 * 
	 * @param type $msg
	 */
	public function error() {
		if(ENVIRONMENT == 'DEV'){
			Helper::raiseError(debug_backtrace(), $this->errorMsg);
		}else{
			file_put_contents($this->logFile, $this->errorMsg, FILE_APPEND);
		}
	}

	public function fetch($result = NULL) {
		if(!$result){
			$result = $this->result;
		}
		return $result->fetch(parent::FETCH_ASSOC);
	}

	public function fetch_all($sql) {
		$result = $this->query($sql);
		if(!is_object($result)){
			return $result;
		}
//		echo($sql);
		$list = $result->fetchAll(parent::FETCH_ASSOC);
		$this->checkResult();
		return $list;
	}

	public function fetch_first($sql) {
		return $this->query($sql)->fetch(parent::FETCH_ASSOC);
	}

	public function free() {
		
	}

	public function insert_id() {
		return $this->lastInsertId();
	}

	/**
	 * Execute special SELECT SQL statement
	 *
	 * @param string  => SQL statement for execution
	 */
	public function query($sql) {
		if($sql){
			$this->sql = $sql;
		}
		
		$retuenRow = FALSE;
		$execArr = array('UPDATE', 'REPLACE INTO', 'DELETE');
		$tmp = trim(strtoupper($this->sql));
		foreach ($execArr as $item){
			// 如果SQL语句属于以上定义的任一种，则返回影响条数
			if(strpos($tmp, $item) === 0){// 当$item所定义的字符串在SQL代码的第一出现
				$retuenRow = TRUE;
				break;
			}
		}
		
		if($retuenRow){
			$return = $this->exec($sql);
		} else {
			$this->result = $return = parent::query($sql);
		}

		$this->checkResult();
		return $return;

		/*
		$this->cache = FALSE;
		if($this->cache || ENTIRE_CACHE == 1){
			$key  = md5($this->sql);
			$data = Helper::getMemcacheInstance()->key($key)->get();
			if($data){
				return $data;
			}
		}
		*/
	}


	/**
	 * Check result for the last execution
	 *
	 * @param null
	 * @return null
	 */
	private function checkResult() {
		if ($this->errorCode() != $this->successCode) {
			$this->success = false;
			$error = $this->errorInfo();
			$traceInfo = debug_backtrace();

			if (ENVIRONMENT == 'DEV') {
				Helper::raiseError($traceInfo, $error[2], $this->sql);
			} else {
				// Log error SQL and reason for debug
				$errorMsg = getClientIP(). ' | ' .date('Y-m-d H:i:s') .NL;
				$errorMsg .= 'SQL: '. $this->sql .NL;
				$errorMsg .= 'Error: '.$error[2]. NL;

				$title =  'LINE__________FUNCTION__________FILE______________________________________'.NL;
				$errorMsg .= $title;

				foreach ($traceInfo as $v) {
					$errorMsg .= $v['line'];
					$errorMsg .= $this->getUnderscore(10, strlen($v['line']));
					$errorMsg .= $v['function'];
					$errorMsg .= $this->getUnderscore(20, strlen($v['function']));
					$errorMsg .= $v['file'].NL;
				}

				file_put_contents($this->logFile, NL.$errorMsg, FILE_APPEND);

				if(defined('API_MODE')){
					eand(ERR_UNKNOWN);
				}

				return false;
			}
		}else{
			$this->success = true;
		}
	}


	public function table($table) {
		
	}

	private function getUnderscore($total = 10, $sub = 0) {
		$result = '';
		for($i=$sub; $i<= $total; $i++){
			$result .= '_';
		}
		return $result;
	}

}
