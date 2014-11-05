<?php

/**
 * File: parse.php
 * Functionality: 
 * Author: hao
 * Date: 2014-10-21 14:52:32
 */
class parse {
	
	static $options;
	
	/**
	 * 合并多个field定义，最终返回格式化后的字符串
	 * warning：暂未对多表联结支持，请勿使用本方法合并多表链接的参数
	 * 
	 * @param type $fields
	 * @return Boolean|string
	 */
	static protected function mergeField($fields){
		if(!is_array($fields)){
			return false;
		}

		foreach($fields as $key => $val){
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$fields[$key] = self::parseField($val);
			}
		}

		return implode(',', $fields);
	}

	/**
	 * 合并多个where定义，最终返回格式化后的字符串
	 * warning：暂未对多表联结支持，请勿使用本方法合并多表链接的参数
	 * warning：暂时只支持使用AND逻辑符连接，如果需要使用OR或其他逻辑符号，请勿使用本方法
	 * 
	 * @param type $wheres
	 * @return boolean
	 */
	static protected function mergeWhere($wheres){
		if(!is_array($wheres)){
			return false;
		}

		foreach($wheres as $key => $val){
			if(empty($val)){
				continue;
			}
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$where[$key] = self::parseWhere($val);
			}

			//替换掉由parseWhere生成的WHERE
			$where[$key] = '('. str_replace('WHERE', '', $where[$key]) .')';
		}

		return implode(' AND ', $where);
	}


	/**
	 * 合并多个order定义，最终返回格式化后的字符串
	 * warning：暂未对多表联结支持，请勿使用本方法合并多表链接的参数
	 * 
	 * @param type $orders
	 * @return Boolean|string
	 */
	static protected function mergeOrder($orders){
		if(!is_array($orders)){
			return false;
		}

		foreach($orders as $key => $val){
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$orders[$key] = self::parseOrder($val);
			}
		}

		return implode(',', $orders);
	}


	/**
	 * 根据链式操作生成SQL代码
	 * @auth 小皓
	 * @date 2014-5-13
	 * @return string SQL语句
	 */
	static protected function parseOptionSQL($fieldsParam = '', $whereParam = ''){
		$fields = $where = $orderby = $limit = $join = '';
		if(isset(self::$options['scope'])){//如果定义了命名范围，则调用方法处理

		}

		if(isset(self::$options['fields'])){
			$fields = self::$options['fields'];
			unset(self::$options['fields']);
		} elseif(!empty($fieldsParam)){
			$fields = $fieldsParam;
		}

		if(isset(self::$options['where'])){
			$where = self::$options['where'];
			unset(self::$options['where']);
		} elseif(!empty($whereParam)){
			$where = $whereParam;
		}

		if(isset(self::$options['orderby'])){
			$orderby = self::$options['orderby'];
			unset(self::$options['orderby']);
		}

		if(isset(self::$options['limit'])){
			$limit = self::$options['limit'];
			unset(self::$options['limit']);
		}

		if(isset(self::$options['join'])){
			$join = self::$options['join'];
			unset(self::$options['join']);
		}

		if(isset(self::$options['group'])){
			$group = self::$options['group'];
			unset(self::$options['group']);
		}

		return self::parseSQL($fields, $where, $orderby, $limit, $join, $group);
	}

	/**
	 * 根据传入的field值生成SQL需要读取的字段内容
	 * 2014-5-20 change by 小皓 新增多表联结自动添加别名操作
	 *
	 * @auth 小皓
	 * @date 2013-11-20
	 * @param type $field
	 * @return string
	 */
	static protected function parseField($field, $join = false){
		$sqlfield = '';
		if(is_array($field)){
			$sqlfield = self::_parseField($field, $join);
		} else {
			$sqlfield = $field;
		}

		if(empty($sqlfield)){
			$sqlfield = '*';
		}

		//删除拼接出来后末尾的逗号
		return rtrim($sqlfield, ',');
	}

	/**
	 * 私有的字段拼接方法，可递归调用
	 * 
	 * @param mixed $field	需要拼接的字段
	 * @param mixed $alias	如果传入的是布尔型，说明当前查询是多表联结查询，需要判断是否自动加别名。如果传入的是字符串，则为当前表别名，自动拼接
	 */
	static private function _parseField($field, $alias = ''){
		$sqlfield = '';
		foreach($field as $key => $val){
			if($alias === true && is_string($key) && is_array($val)){//如果是多表联结，且$key为字符串$val为数组，则以key为别名继续拼装
				$sqlfield .= self::_parseField($val, $key);		//递归调用自身
			} elseif(is_string($alias)) {
				$sqlfield .= self::parseKey($val, $alias) .',';
			} else {
				$sqlfield .= self::parseKey($val) .',';
			}
		}

		return $sqlfield;
	}
	
	/**
	 * 处理命名空间，将命名空间的数据转化为SQL并返回
	 * 
	 * @auth 小皓
	 * @since 2014-08-27
	 * @return array
	 */
	static protected function parseScope(){
		$scope = self::$options['scope'];
		unset(self::$options['scope']);
		$return = $data = array();

		if(!is_array($scope)){
			return array();
		}

		$isList = array_reduce(array_map('is_numeric', array_keys($scope)), 'and', true);
		if($isList){
			foreach($scope as $item){
				$sqlArr = self::_parseScope($item);
				$data['field'][] = $sqlArr['field'];
				$data['where'][] = $sqlArr['where'];
				$data['order'][] = $sqlArr['order'];
			}
			
			self::mergeField($data['field']);
			self::mergeWhere($data['where']);
			self::mergeOrder($data['order']);
		} else {
			self::_parseScope($scope);
		}
		return $return;
	}
	
	static private function _parseScope($scope){
		//可用于命名范围合并的内容
		$actionArr = array('field', 'where', 'order');
		$return = array();

		foreach($actionArr as $item){
			if(isset($scope[$item])){
				if(is_array($scope[$item]) && isset(self::$options['alias'])){
					$scope[$item][self::$options['alias']] = $scope[$item];
				}
				$functionName = 'parse'. ucfirst($item);
				$return[$item] = self::$options[$item] = call_user_func(
					array($this, $functionName),
					array($scope[$item])
				);
			}
		}
		
		return $return;
	}

	/**
	 * 根据传入的where变量生成判断条件
	 * 2014-5-17 change by 小皓 从TP中复制了同名方法，并删除不需要的部分，将中间部分拆分至私有方法，现支持多维数组拼装复杂的WHERE操作了
	 * 2014-5-20 change by 小皓 添加自动加别名方法
	 *
	 * @auth 小皓
	 * @since 2013-11-20
	 * @param mixed $where
	 * @param boolean $join
	 * @return string
	 */
	static protected function parseWhere($where, $join = false){
		if($join && is_array($where)){//如果是联表查询，且where是数组，则判断自动加别名if(isset($where['_logic'])) {
			// 定义逻辑运算规则 例如 OR XOR AND NOT
			if(isset($where['_logic'])) {
				$logic    =   ' '.strtoupper($where['_logic']).' ';
				unset($where['_logic']);
			} else {
				// 默认进行 AND 运算
				$logic    =   ' AND ';
			}
			foreach($where as $key => $val){
				if(is_string($key) && is_array($val)){//如果$key为字符串，且val是数组，则以key为别名拼接where
					$sqlwhere .= $logic . self::_parseWhere($val, $key);
				} else {
					$sqlwhere .= $logic . self::_parseWhere($where);
					break;
				}
			}

			$sqlwhere = substr($sqlwhere, strlen($logic));
		} elseif(is_numeric($where)){//如果where是纯数字，则将其作为主键(id)来查询
			$sqlwhere = self::_parseWhere(array('id' => $where));
		} elseif(is_string($where)){
			$sqlwhere = $where;
		} else {
			$sqlwhere = self::_parseWhere($where);
		}

		if(!empty($where) && strpos(trim(strtolower($sqlwhere)), 'where') !== 0){//自动拼接where
			$sqlwhere = ' WHERE '. $sqlwhere;
		}

		return $sqlwhere;
	}

	/**
	 * 拼接WHERE SQL语句私有方法，可用于重复拼接
	 * 
	 * @auth 小皓
	 * @since 2014-5-17
	 * @param mixed $where
	 * @param boolean $join
	 * @param string $alias
	 * @return type
	 */
	static private function _parseWhere($where, $alias = ''){
		$sqlwhere = '';
		if(is_string($where)) {
			// 直接使用字符串条件
			$sqlwhere = $where;
		} elseif(is_array($where)) { // 使用数组条件表达式
			if(isset($where['_logic'])) {
				// 定义逻辑运算规则 例如 OR XOR AND NOT
				$logic    =   ' '.strtoupper($where['_logic']).' ';
				unset($where['_logic']);
			}else{
				// 默认进行 AND 运算
				$logic    =   ' AND ';
			}

			if(isset($where['_op'])){//定义了运算符则
				$operator = $where['_op'];
				unset($where['_op']);

				$search = array('eq', 'ne', 'gt', 'ge', 'lt', 'le');
				$operatorArr = array('=', '!=', '>', '>=', '<', '<=');

				if(!in_array($operator, $operatorArr)){//定义的运算符为转义，则替换
					$operator = str_replace($search, $operatorArr, strtolower($operator));
				}
			} else {//未定义默认为=
				$operator = '=';
			}

			foreach ($where as $key => $val){
				$sqlwhere .= "( ";

				if(is_array($val)) {//$val为数组，调用_parseMultiWhere拼接
					$sqlwhere .= self::_parseMultiWhere($key, $val, $alias);
				} elseif(is_string($key)){//如果key为字符串，则拼接为$key=$val的格式
					$key = self::parseKey($key, $alias);
					if($operator == 'lr'){
						$sqlwhere .= $key .' LIKE \''. $val .'%\'';
					} elseif($operator == 'll'){
						$sqlwhere .= $key .' LIKE \'%'. $val .'\'';
					} elseif($operator == 'l'){
						$sqlwhere .= $key .' LIKE \'%'. $val .'%\'';
					} else {
						$sqlwhere .= $key . $operator . self::parseValue($val);
					}
				} else {//如果$key不为字符串，则代表$val是一段完整的SQL语句，直接拼装
					$sqlwhere .= $val;
				}

				$sqlwhere .= ' )'. $logic;
			}

			$sqlwhere = substr($sqlwhere,0, -strlen($logic));
		}

		return $sqlwhere;
	}


	/**
	 * 当使用数组拼接Where的时候调用本方法处理
	 * 
	 * @param mixed $field	当field为字符串时，则拼接为field IN('1', '2', '3')，否则调用_parseWhere将value拼接为新的SQL
	 * @param array $value
	 * @return type
	 */
	static private function _parseMultiWhere($field, $value, $alias = ''){
		if(is_string($field)){//$val为数组且$key为字符串，则拼接为field IN ('1', '2', '3')的格式
			$valueList = implode('\',\'', $value);

			$sqlwhere = self::parseKey($field, $alias) ." IN ('{$valueList}')";
		} else {
			$sqlwhere = self::_parseWhere($value, $alias);
		}

		return $sqlwhere;
	}

	/**
	 * 处理由Search方法引入的搜索数据
	 * 处理完后将于原有的Where合并
	 * 
	 * @param type $oldwhere	旧的Where
	 * @return string
	 */
	static protected function parseSearch($oldwhere, $fieldSetting){
		$search = self::$options['search'];
		unset(self::$options['search']);

		$where = array();
		foreach($search as $key => $val){
			$field = $fieldSetting;
			//如果表内不存在
			if(!isset($field) || (empty($val) && $val !== '0')){//数据库中不存在该字段，或搜索条件不合法
				continue;
			}

			$whereTmp = array($key => $val);

			//字段为字符串类型，则使用 LIKE '%...%'进行搜索，其余的使用=
			if(in_array(strtolower($field['type']), array('char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext'))){//字段为字符串
				$whereTmp['_op'] = 'l';
			}

			$where[] = $whereTmp;
		}
		
		if(isset(self::$options['alias'])){
			$where = array(
				self::$options['alias'] => $where
			);
		}
		
		return ' WHERE '. self::mergeWhere(array($oldwhere, $where));
	}

	/**
	 * 格式化group后的内容
	 *
	 * @auth 小皓
	 * @date 2014-6-8
	 * @param type $group
	 */
	static protected function parseGroup($group){
		if(is_array($group)){
			$group = 'GROUP BY '. self::_parseGroup($group);
		}

		return $group;
	}

	/**
	 * 格式花数组格式的group
	 *
	 * @auth 小皓
	 * @date 2014-6-8
	 * @param type $group
	 * @param type $alias
	 * @return type
	 */
	static private function _parseGroup($group, $alias = ''){
		$sqlgroup = '';
		foreach($group as $key => $val){
			if(is_string($val)) {
				$sqlgroup .= ($alias ? $alias .'.' : '') . self::parseKey($val);
			} elseif(is_array($val) && is_string($key)){
				$sqlgroup .= self::_parseField($val, $key) .',';
			}
		}

		return rtrim($sqlgroup, ',');
	}

	/**
	 * 根据参数生成排序SQL
	 *
	 * @auth 小皓
	 * @date 2013-11-20
	 * @param type $orderby
	 * @return string
	 */
	static protected function parseOrder($orderby) {
		$sqlorder = '';
		if(is_array($orderby)){
			$tmpArr = array();
			foreach($orderby as $key => $value){
				$tmpStr = $key;
				if($value){
					$tmpStr .= ' DESC';
				}

				$tmpArr[] = $tmpStr;
			}

			$sqlorder = implode(',', $tmpArr);
		} else {
			$sqlorder = $orderby;
		}

		return $sqlorder;
	}

	/**
	 * 根据传入的limit值生成范围
	 *
	 * @auth 小皓
	 * @date 2013-11-20
	 * @param type $limit
	 */
	static protected function parseLimit($limit){
		if(is_array($limit)){
			$countlimit = count($limit);
			$sqllimit = $countlimit >= 2 ? "{$limit[0]}, {$limit[1]}" : '';
		} else {
			$sqllimit = $limit;
		}

		if(!empty($sqllimit) && strpos($sqllimit, 'LIMIT') === false){
			$sqllimit = ' LIMIT '. $sqllimit;
		}

		return $sqllimit;
	}

	/**
	 * 根据传入的join参数生成table内容
	 * 
	 * @auth 小皓
	 * @date 2014-5-13
	 * @param array $join
	 * @return string
	 */
	static protected function parseTable($join = array()){
		//此处用于跨库查询支持
		//跨库查询使用Database方法或$_database属性设置，如果两者存在，优先使用Database传入的库名
		$database = '';
		if(isset(self::$options['database'])){
			$database = self::parseField(self::$options['database']) .'.';
			unset(self::$options['database']);
		} elseif(!empty(self::_database)) {//设置了$_database属性
			$database = self::parseField(self::_database) .'.';
		}

		$sqltable = $database . self::table;
		if(isset(self::$options['alias'])){
			$sqltable .= ' AS '. self::$options['alias'];
			unset(self::$options['alias']);
		}
		if(!empty($join)){
			foreach($join as $item){
				$sqltable .= ' '. $item['type'] .' JOIN '. $database . TB_PREFIX .$item['table'];
				if(!empty($item['on'])){
					$sqltable .= ' ON '. $item['on'];
				}
			}
		}

		return $sqltable;
	}
}
