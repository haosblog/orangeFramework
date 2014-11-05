<?php 
/**
 * File: L_Query.class.php
 * Functionality: 利用query 进行html匹配 ,搜索等功能
 * Remark: 
 */

include BASE_PATH . '/plugin/phpQuery/phpQuery.php';

class Query{
	private $query = '';
	
	function __get($name){
		return $this->$name;	
	}
	
	function __construct(){
		
	}
}

?>