<?php
/**
 * File: DB.php
 * Functionality: 
 * Author: hao
 * Date: 2014-10-8 12:02:11
 */

class DB {
	
	static public $db;
	
	/**
	 * 初始化数据库类，引入驱动实例化驱动对象
	 * 
	 * @return type
	 */
	static public function init(){
		if(self::$db != null){
			return self::$db;
		}

		$driverClass = 'DB_'. DB_DRIVER;
//		$driverFile = TRUNK_PATH .'/driver/db/'. $driverClass .'.class.php';
//		
//		require_once $driverFile;

		self::$db = new $driverClass();
		return self::$db;
	}

	/**
     * 关闭数据库
     * @access public
     * @return void
     */
    static public function close(){
		self::$db->close();
	}
	
	// ********* Execute transaction ********* //
	/**
	 * Start a transaction
	 *
	 * @param null
	 * @return true on success or false on failure
	 */
	static public function beginTransaction() {
		self::$db->beginTransaction();
	}

	/**
	 * Commit a transaction
	 *
	 * @param null
	 * @return true on success or false on failure
	 */
	static public function Commit() {
		self::$db->commit();
	}

	/**
	 * Rollback a transaction
	 *
	 * @param  null
	 * @return true on success or false on failure
	 */
	static public function Rollback() {
		self::$db->rollBack();
	}

	/**
	 * 释放查询结果
	 * @access public
	 */
	static public function free(){
		self::$db->free();
	}

	static public function error(){
		self::$db->error();
	}

	/**
	 * 给表名添加前缀
	 *
	 * @param type $table
	 * @return type
	 */
	static public function table($table){
		return TB_PREFIX . $table;
	}

	/**
	 * 执行查询 返回数据集
	 * @access public
	 * @param string $sql  sql指令
	 * @return mixed
	 */
	static public function query($sql){
		return self::$db->query($sql);
	}

	static public function fetch($result){
		return self::$db->fetch($result);
	}

	/**
	 *
	 * @param type $sql
	 * @return type
	 */
	static public function fetch_first($sql){
		return self::$db->fetch_first($sql);
	}


	static public function fetch_all($sql){
		return self::$db->fetch_all($sql);
	}

	static public function insert($table, $data, $getid = FALSE){
		self::_updatesql($table, $data);
		
		if($getid){
			return self::$db->insert_id();
		} else {
			return self::$db->success;
		}
	}

	static public function update($table, $data, $where = '', $self = FALSE){
		return self::_updatesql($table, $data, 'UPDATE', $where, $self);
	}
	
	
	static public function replace($table, $data, $where){
		return self::_updatesql($table, $data, 'REPLACE', $where);
	}
	
	static private function _updatesql($table, $data, $type = 'INSERT', $where = '', $self = FALSE){
		if(!in_array($type, array('INSERT', 'REPLACE', 'UPDATE'))){
			return false;
		}

		$sql = (
				in_array($type, array('INSERT', 'REPLACE')) ?
					$type .' INTO' :
					"UPDATE"
				). " $table  SET ";
		if($self){
			foreach ($data as $key => $value) {
				if (strpos($value, '+') !== FALSE) {
					list($flag, $v) = explode('+', $value);
					$sets[] = "`$key` = `$key` + '$v'";
				} elseif (strpos($value, '-') !== FALSE) {
					list($flag, $v) = explode('-', $value);
					$v = intval($v);
					$sets[] = "`$key` = `$key` - '$v'";
				} else {
					$sets[] = "`$key` = '$value'";
				}
			}
		} else {
			foreach ($data as $key => $value) {
				$sets[] = "`$key` = '$value'";
			}
		}
		$sql .= implode(',', $sets). ' ';

		if($type == 'UPDATE' && !empty($where)){
			$sql .= ' '. $where;
		}
		return self::$db->query($sql);
	}


	static public function delete($table, $where){
		$sql = 'DELETE FROM ' . $table . ' '. $where;
		return self::query($sql);
	}

	/**
	 * 获取上一次运行的ID
	 */
	static public function insert_id(){
		return self::$db->insert_id();
	}

}