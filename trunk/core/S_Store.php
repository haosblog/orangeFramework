<?php
/**
 * File: S_Store.php
 * Created on : 2014-3-12, 23:09:04
 * copyright 小皓 (C)2013-2099 版权所有
 * www.haosblog.com
 *
 * 数据层基类
 * TODO 基类尚需完善
 */
abstract class S_Store {

	protected  $scope = array();		//明明范围
	public $pageTotal = 0;
	public $pageSUM = 0;			//总页数，用于存储分页信息


	/**
	 * 根据分页数据生成limit
	 *
	 * @param type $page
	 * @param type $pageCount
	 * @return string
	 */
	protected function getLimit($page, $pageCount, $showall = false){
		if($pageCount == 0){
			return '';
		}

		$start = ($page - 1) * $pageCount;
		if($showall){
			return ' LIMIT '. $start .', -1';
		}
		return ' LIMIT '. $start .','. $pageCount;
	}

	/**
	 * 生成模型对象，如果没有输入分组名则使用当前data类分组
	 *
	 * @param type $model
	 * @return type
	 */
	protected function M($model){
		return $this->load($model);
	}

	protected function load($model){
		return Helper::loadModel($model);
	}

	/**
	 * 生成数据对象，如果没有输入分组名则使用当前data类分组，调用_joint方法判断
	 *
	 * @param type $data
	 * @return type
	 */
	protected function D($store){
		return Helper::loadStore($store);
	}

	protected function getCurrentPage($total, $pageCount){
		return ceil($total / $pageCount);
	}
}
