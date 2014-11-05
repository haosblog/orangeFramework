<?php 
/**
 * File: L_Xunsearch.class.php
 * Functionality: 利用 xunsearch 进行搜索纠错,分词等功能
 * Remark: 
 */
include BASE_PATH . '/plugin/xunSearch/lib/XS.php';

class Xunsearch {
	private $xs = '';
	
	private $index = '';
	
	private $search = '';
	
	function __get($name) {
		return $this->$name;
	}
	
	function __construct() {
		try {
			$this->xs = new XS('new'); 	 // demo 为项目名称，配置文件是：$sdk/app/demo.ini
			$this->index = $this->xs->index;
			$this->search = $this->xs->search;
		} catch (XSException $e) {
			echo $e . "\n" . $e->getTraceAsString() . "\n"; // 发生异常，输出描述
		}
	}
	
	public function swichDb($db = 'new') { //切换检索库
		$this->xs = new XS($db);
		$this->index = $this->xs->index;
		$this->search = $this->xs->search;
	}
	
}
?>