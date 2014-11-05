<?php
/**
 * copyright 小皓 (C)2013-2099 版权所有
 *
 * 数据库基础类
 */

class mysql implements database {
	public	$pre;
	private	$conn;
	public	$result;
	public	$rownum;

	/**
	 * 构造方法
	 * @param type $config 设置数据库链接信息，如果为空，则调用sysconfig
	 */
	public function __construct(){
		$config = Helper::loadConfigData('DB');

		$this->pre = isset($config['pre']) ? $config['pre'] : 'hao_';
		$dbhost = isset($config['dbhost']) ? $config['dbhost'] : 'localhost';
		$dbhost .= isset($config['port']) ? ':'. $config['port'] : '';
		$dbname = $config['dbname'];
		$dbuser = isset($config['dbuser']) ? $config['dbuser'] : 'root';
		$dbpswd = isset($config['dbpswd']) ? $config['dbpswd'] : '';
		$charset = isset($config['charset']) ? $config['charset'] : 'UTF8';

		if(isset($config['pconnect']) && $config['pconnect']){
			$this->conn = mysql_pconnect($dbhost, $dbuser, $dbpswd, 131072);
		}else{
			$this->conn = mysql_connect($dbhost, $dbuser, $dbpswd, true, 131072);
		}
		mysql_select_db($dbname);
		mysql_query("SET NAMES '$charset'");
	}

	public function __destruct(){
		$this->close();
	}

	/**
     * 关闭数据库
     * @access public
     * @return void
     */
    public function close() {
		if ($this->conn){
			mysql_close($this->conn);
		}
		$this->conn = null;
	}

	/**
	 * 释放查询结果
	 * @access public
	 */
	public function free() {
		if(is_bool($this->result)){
			$this->result = null;
			return true;
		}
		mysql_free_result($this->result);
		$this->result = null;
	}

	public function error(){
		return mysql_error();
	}

	/**
	 * 给表名添加前缀
	 *
	 * @param type $table
	 * @return type
	 */
	public function table($table){
		return $this->pre . $table;
	}

	/**
	 * 执行查询 返回数据集
	 * @access public
	 * @param string $str  sql指令
	 * @return mixed
	 */
	public function query($sql) {
		if(0===stripos($sql, 'call')){ $this->close(); }
		if(stripos($sql, '@_@') !== false){
			$sql = str_replace('@_@', $this->pre, $sql);
		}
		//释放前次的查询结果
		if ( $this->result ) { $this->free(); }
		$this->result = mysql_query($sql);
		if ( false === $this->result ) {
			$this->error();
			return false;
		} else {
			//$this->rownum = mysql_num_rows($this->result);
			return $this->result;
		}
	}

	public function fetch($result){
		if(!$result){
			return false;
		}
		return mysql_fetch_array($result);
	}

	/**
	 *
	 * @param type $sql
	 * @return type
	 */
	public function fetch_first($sql){
		$result = $this->query($sql);
		$returndata = $this->fetch($result);
		$this->free();
		return $returndata ? $returndata : false;
	}

	public function fetch_all($sql){
		$result = $this->query($sql);
		$return = array();
		while($row = $this->fetch($result)){
			$return[] = $row;
		}

		return $return;
	}

	public function insert($table, $data, $getid = false){
		$this->updatesql($table, $data);
		if($getid){
			return $this->insert_id();
		}

		return true;
	}

	public function update($table, $data, $where = ''){
		$this->updatesql($table, $data, 'update', $where);
	}

	private function updatesql($table, $data, $type = 'insert', $where = ''){
		$sql = $type == 'insert' ? "INSERT INTO `$table` SET" : "UPDATE `$table` SET";
		foreach($data AS $key => $value){
			$sql .= " `$key`='$value',";
		}
		$sql = rtrim($sql, ',');
		if($type == 'update' && !empty($where)){
			$sql .= " WHERE $where";
		}
		$this->query($sql);
	}

	public function insert_id(){
		return mysql_insert_id($this->conn);
	}
}