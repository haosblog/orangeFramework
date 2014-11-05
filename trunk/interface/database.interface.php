<?php
/**
 * File: database.php
 * Functionality: 
 * Author: hao
 * Date: 2014-8-8 11:57:53
 */

interface database {
	/**
     * 关闭数据库
     * @access public
     * @return void
     */
    public function close();

	/**
	 * 释放查询结果
	 * @access public
	 */
	public function free();

	public function error();

	/**
	 * 给表名添加前缀
	 *
	 * @param type $table
	 * @return type
	 */
	public function table($table);

	/**
	 * 执行查询 返回数据集
	 * @access public
	 * @param string $str  sql指令
	 * @return mixed
	 */
	public function query($sql);

	public function fetch($result);

	/**
	 *
	 * @param type $sql
	 * @return type
	 */
	public function fetch_first($sql);

	public function fetch_all($sql);

	public function insert_id();
}
