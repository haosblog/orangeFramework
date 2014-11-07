<?php
/**
 * File: M_Model.class.php
 * Functionality: Core PDO model class
 * Author: Nic XIE
 * Date: 2013-2-28
 * Note:
 *	1 => This class requires PDO support !
 *	2 => $conn MUST BE set to static for transaction !
 * ---------------- DO NOT MODIFY THIS FILE UNLESS YOU FULLY UNDERSTAND ! -------------
 */

class M_Model {

	//change By 小皓。数据仓库所需字段
	public $data = array();				//查询到的数据存储数组
	public $count = 0;					//总数统计,用于分页用
	private $totalSql = '';
//	protected $expire = 3600;           // SQL 语句默认缓存的过期时间: 1小时

	protected $sql = '';				// 上一次执行的SQL
	protected $originalTable = '';		//原始表名，用于分表
	protected $table = '';				//表名
	protected $modelname;				// 本模型名
	protected $_database = '';			//当前模型使用的数据库，默认为空，用于支持跨库查询


	// The result of last operation: 0 => failure,  1 => success
	private $success = false;

	// SQL log file: Log SQL error for debug if not under DEV
	private $logFile = '';

	//Author 小皓。
	//date 2014-5-9
	//新增自动验证、自动装载部分
	protected $options = array();				//2014-05-12新增，用于进行链式操作的参数数组
	protected $_scope = array();				//2014-07-07新增，用于定义命名范围。命名范围请在模型类中定义，关于命名范围的使用，参照Scope方法中的注释
	protected $_fieldsSetting = array();		//用于数据筛选用的字段设置
	protected $_default = array();				//字段默认值
	protected $_validate = array();				//用于自动验证的验证规则，由模型定义
	protected $_needField = array();			//在插入模式自动装载数据时如果某些必填字段未填，则会存储到本属性中，供Insert方法检查，如果执行Insert时该属性中的字段未填满，则报错
	protected $_errMsg = '';					//自动验证产生的报错信息
	protected $_errNo = 0;						//自动验证产生的报错编号
	protected $_errLevel = 0;					//错误等级
												//0=>没有错误，
												//1=>严重错误，无法执行任何操作
												//2=>无害错误。部分必填信息为空，但用于执行update操作并无妨

	/**
	 * Constructor
	 * <br /> 1: Connect to MySQL
	 *
	 * @param string => use default DB if parameter is not specified !
	 * @return null
	 */
	public function __construct($tableName = ''){
		$this->getTableName($tableName);

		$this->logFile = LOG_PATH .'/sql/'. CUR_DATE .'.log';
		if(!file_exists($this->logFile) && ENVIRONMENT != 'DEV'){
			touch($this->logFile);
		}

		
		//如果没有设置本模型的表名，则抛出错误，避免后面无法加载表结构缓存
		if(!$this->table){//尚未设置表名，一般是模型的构造函数里顺序不对导致
//			throw new Exception('$this->table表名未设置，请检查construct中');
			return true;
		}

		//读取字段缓存
		if(empty($this->_fieldsSetting)){//未设置_fieldsSetting，则加载缓存
			$fieldCacheFile = '/db/'. $this->table .'.php';
			$fieldSetting = getCache($fieldCacheFile);
			if(!$fieldSetting){//无法从缓存中读取字段，从数据库中读取，并创建缓存
				$sql = 'DESC '. $this->table;
				$fields = DB::fetch_all($sql);
				foreach($fields as $item){
					$key = $item['Field'];
					$value = array(
						'null' => $item['Null'],
						'default' => $item['Default'],
						'key' => $item['Key'],
						'extra' => $item['Extra'],
					);
					preg_match("/(.*)\\((\d*)\\)/is", $item['Type'], $type, PREG_OFFSET_CAPTURE);
					$value['type'] = $type[1][0];
					$value['length'] = $type[2][0];
					if($value['key'] == 'PRI'){
						$fieldSetting['_pk'] = $value;
						$fieldSetting['_pk']['field'] = $key;
					}

					$fieldSetting[$key] = $value;
				}
				saveCache($fieldCacheFile, $fieldSetting);
			}

			$this->_fieldsSetting = $fieldSetting;
		}
	}


	/**
	 * 利用__call方法实现一些特殊的Model方法
	 * @access public
	 * @param string $method 方法名称
	 * @param array $args 调用参数
	 * @return mixed
	 */
	public function __call($methodOri, $args) {
		$method = strtolower($methodOri);
		if(in_array($method,array('count','sum','min','max','avg'),true)){
			// 统计查询的实现
			$field = isset($args[0]) ? $this->parseField($args[0]) : '*';
			if($args[1]){//如果传入了第二个参数，则将第二个参数的信息写入Where中（也可以使用链式传入）
				$this->Where($args[1]);
			}
			$data =  $this->Field(strtoupper($method).'('.$field.') AS total')->SelectOne();
			return $data['total'];
		} elseif(substr($method, 0, 10) == 'getfieldby'){
			// 根据某个字段获取记录的某个值
			// 需要查询的字段，由于linux下mysql区分大小写，因此必须使用原始的参数而不是转为小写的
			// 需要将第一个字母转为小写，避免出错
			$name = lcfirst(substr($methodOri,10));
			if($name == 'iD'){ $name = 'id'; }
			$where[$name] = $args[1];
			return $this->Where($where)->getField($this->parseField($args[0]));
		} elseif(substr($method, 0, 8) == 'selectby'){
			// 根据某个字段获取记录
			// 需要查询的字段，由于linux下mysql区分大小写，因此必须使用原始的参数而不是转为小写的
			// 需要将第一个字母转为小写，避免出错
			$name = lcfirst(substr($methodOri,8));
			if($name == 'iD'){ $name = 'id'; }
			$where[$name] =$args[0];
			$this->Where($where);
			// 通过Field方法设置读取的字段，如果未使用，则默认读取所有
			// 第二个参数表示是否开启多行查询模式，默认为true，即返回一个记录集，如果传入为false，则返回一条记录
			if(isset($args[1]) && !$args[1]){//关闭多行模式，已传入第二个参数（isset($args[1])），且为false
				$return = $this->SelectOne();
			} else {
				$return = $this->Select();
			}
			return $return;
		} elseif(substr($method, 0, 5) == 'getby'){
			// 根据某个字段获取一条记录
			// 需要查询的字段，由于linux下mysql区分大小写，因此必须使用原始的参数而不是转为小写的
			// 需要将第一个字母转为小写，避免出错
			$name = lcfirst(substr($methodOri,5));
			if($name == 'iD'){ $name = 'id'; }
			$where[$name] =$args[0];

			return $this->Where($where)->SelectOne();
		}
	}


	/**
	 * Add table prefix
	 *
	 * @param string => target table
	 * @return table with TB_PREFIX
	 */
	public function addPrefix($table) {
		$this->table = TB_PREFIX . $table;
		return $this->table;
	}

	/**
	 * 用于在分表查询中更改表名
	 *
	 * @param type $tableid
	 * @return \model 返回自身，支持链式操作
	 */
	public function setTable($tablename){
		$this->table = DB::table($tablename);

		return $this;
	}


	/**
	 * 重置分表中的表名为原始名
	 *
	 * @return \model 返回自身，支持链式操作
	 */
	public function resetTable(){
		$this->tablename = DB::table($this->tablenameOri);

		return $this;
	}


	/**
	 * Switch DB
	 *
	 * @param string => target db
	 * @return null
	 */
	public function selectDB($db = '') {
		DB::query('use '. $db);
		return $this;
	}


	/**
	 * 链式操作中的field，构造SQL查询中的字段部分
	 * 如果传入多个参数，则多个参数被集合到一个array中作为要查询的字段（多表联结不适用）
	 * 
	 * @param type $field	需要查询的字段，可以是直接的SQL字符串，也可以是一个array（字段集合）。不传入或传入为false则查询所有字段(*)
	 * @return \M_Model
	 */
	final public function Field($field = false){
		//如果传入的field为true，则载入所有字段
		if(empty($field)) {
			$field = '*';
		}

		if(func_num_args() > 1){
			$this->options['fields'] = func_get_args();
		} else {
			$this->options['fields'] = $field;
		}

		return $this;
	}

	/**
	 * 链式操作中的Where，构造SQL查询的Where部分
	 * Where支持四种格式的参数：SQL代码字符串、ID值、array、简化操作
	 * array使用方法详见文档
	 * 简化操作适用于查询非常简单，只有一个查询条件，且不存在联表。
	 * 如果只传入一个数字，则会被解析为id=value的SQL（在parseWhere中处理）
	 * 简化操作本方法将接受2~3个string参数，分别为：field、value、operation
	 * 根据3个参数，将生成如下格式的array格式的Where：
	 * array( field => value, _op => operation )
	 * 
	 * @param string|array $where
	 * @param string arg2	第二个参数如果传入，则表示使用第二套方案
	 * @return \M_Model
	 */
	final public function Where($where){
		if(func_num_args() > 1 && is_string($where)){
			$options = func_get_args();
			$this->options['where'] = array(
				$options[0] => $options[1]
			);
			
			if(isset($options[2])){
				$this->options['where']['_op'] = $options[2];
			}
		} else {
			$this->options['where'] = $where;
		}

		return $this;
	}
	
	final public function Database($database){
		$this->options['database'] = $database;
		
		return $this;
	}

	/**
	 * 传入关键词进行搜索使用
	 * 
	 * @create 2014-8-11 10:20
	 * @auth 小皓
	 * @param array $keywordArr
	 * @return \M_Model	返回自身供链式操作使用
	 */
	final public function Search($keywordArr){
		if(is_array($keywordArr)){//keyword必须为数组
			$this->options['search'] = $keywordArr;
		}
		
		return $this;
	}

	final public function Group($group){
		if(is_string($group)){
			$group = ' GROUP BY '. $group;
		}

		$this->options['group'] = $group;
		return $this;
	}

	final public function Order($orderby){
		if(empty($orderby)){//$orderby为空则不作处理
			return $this;
		}
		$orderby = strpos($orderby, 'ORDER') === false ? ' ORDER BY '. $orderby : $orderby;

		$this->options['orderby'] = $orderby;
		return $this;
	}

	final public function Limit($limit){
		if(func_num_args() == 2){
			$limit = func_get_args();
		}
		if(empty($limit)){//limit为空则不作处理
			return $this;
		}

		$this->options['limit'] = $limit;
		return $this;
	}


	/**
	 * 链式操作中的联表定义
	 * 
	 * @author 小皓
	 * @add 2014-5-12
	 * @param type $table	表名
	 * @param type $on		关联条件，SQL语句
	 * @param string $type	联表类型，默认为空，可选择 LEFT 或 RIGHT
	 * @return obj $this
	 */
	final public function Join($table, $on, $type = ''){
		$this->options['join'][] = array(
			'table' => $table,
			'on' => $on,
			'type' => $type
		);
		return $this;
	}

	/**
	 * 设置命名范围
	 * 命名范围用于定义一些常用的操作，使用时只需要调用本方法传入该操作的别名即可将参数合并
	 * 如房源需要根据发表时间做排序，则可定义以下命名范围：
	 * prote
	 * 
	 * @param type $name
	 * @return obj $this
	 */
	final public function Scope($name){
		$scope = $this->_scope[$name];
		if($scope){//是否存在该定义的命名范围
			if(isset($scope['join'])){
				$this->options['join'][] = $scope['join'];
				unset($scope['join']);
			}

			$this->options['scope'][] = $scope;
		}

		if(func_num_args() > 1){//如果本函数传入了多个参数，则递归本身处理每一个
			$args = func_get_args();
			unset($args[0]);//刚刚已处理过第一个参数，因此剔除
			foreach($args as $item){
				$this->Scope($item);
			}
		}

		return $this;
	}

	public function Alias($alias){
		$this->options['alias'] = $alias;
		return $this;
	}


	/**
	 * Select all records
	 *
	 * @param string  => if null or empty, make it '*', else combine all if is an array, use it if string !
	 * @param string  => where condition
	 * @param string  => order by condition
	 * @param string  => limit condition
	 * @param boolean => return numRows
	 * @return records on success or false on failure or numRows if $numRows is true on success
	 */
	final public function Select($fields = '', $where = '', $orderby = '', $limit = '', $numRows = false, $total = true){
		//如果options属性不为空，则执行链式操作
		if(!empty($this->options)){
			$sql = $this->parseOptionSQL($fields, $where);
		} else {
			$sql = $this->parseSQL($fields, $where, $orderby, $limit);
		}

		if($fields === false){//如果第一个参数为false，则返回SQL用于子查询
			return $sql;
		}

		$this->sql = $sql;
		//echo $sql;

		/*
		$this->cache = FALSE; // 暂停使用 MS
		if($this->cache === FALSE || ENTIRE_CACHE == 1){
			$key  = md5($this->sql);
			$data = Helper::getMemcacheInstance()->key($key)->get();
			if($data){
				return $data;
			}
		}
		*/

		$return = DB::fetch_all($sql);

		if($total){
			$totalQuery = DB::fetch_first($this->totalSql);
			$this->count = $totalQuery['count'];
		}

		if($numRows){
			return $total;
		}

		return $return;
	}


	/**
	 * Select one record
	 *
	 * @param string  => if null or empty, make it '*', else combine all if is an array, use it if string !
	 * @param string  => where condition
	 * @param string  => order by condition
	 * @return records on success or false on failure or numRows if $numRows is true on success
	 */
	final public function SelectOne($fields = '', $where = '', $orderby = ''){
		$limit = (is_string($where) && stripos($where, 'LIMIT') !== false) || (is_string($orderby) && stripos($orderby, 'LIMIT') !== false) ? '' : 'LIMIT 1';
		$query = $this->Select($fields, $where, $orderby, $limit, false, false);

		$data = null;
		if(is_array($query) && !empty($query)){
			$data = $query[0];
		} else {
			return FALSE;
		}

		$this->data = $data;
		return $data;
	}


	/**
	 * Insert | Add a new record
	 *
	 * @param Array => Array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value1')
	 * @return false on failure or inserted_id on success
	 */
	final public function Insert($maps = array()) {
		if($this->_errNo != 0){//错误不可执行insert操作
			return false;
		}
		
		if(!empty($this->_needField)){//如果自动装载数据时产生了未填满的必填字段，则做一次判断
			foreach($this->_needField as $item){
				if(empty($maps[$item])){
					return false;
				}
			}
		}
 
		$table = $this->parseTable();
		$maps = empty($maps) ? $this->data : $maps;
		if (!$maps || !is_array($maps)) {
			return false;
		} else {
			return DB::insert($table, $maps, TRUE);
		}
	}


	/**
	 * Insert | Add a list record
	 *
	 * @param type $data
	 * @return boolean
	 */
	public function MultiInsert($data){
		$sql = "INSERT INTO ". $this->table;
		$sqlFieldArr = array();
		$sqlValueArr = array();
		$first = true;

		foreach($data as $item){
			if(!is_array($item)){
				return false;
			}

			if($first){
				$sqlFieldArr = array_keys($item);

				$sqlFieldStr = implode('`,`', $sqlFieldArr);
				$first = false;
			}

			$tmp = '(\''. implode('\',\'', $item) .'\')';
			$sqlValueArr[] = $tmp;
		}

		$sqlValueStr = implode(',', $sqlValueArr);
		$sql .= "(`$sqlFieldStr`) VALUES $sqlValueStr";

		$this->sql = $sql;
		return DB::query($sql);
	}

	/**
	 * Replace | Add a new record if not exit, update if exits;
	 *
	 * @param Array => Array('field1'=>'value1', 'field2'=>'value2', 'field3'=>'value1')
	 * @return false on failure or inserted_id on success
	 */
	final public function ReplaceInto($maps) {
		$table = $this->parseTable();
		if (!$maps || !is_array($maps)) {
			return false;
		} else {
			return DB::replace($table, $maps);
		}
	}


	/**
	 * Return last inserted_id
	 *
	 * @param null
	 * @return the last inserted_id
	 */
	public function getInsertID() {
		return DB::insert_id();
	}



	/**
	 * Calculate record counts
	 * 该方法与count方法的区别在于，Total不支持定义统计的字段名，仅仅返回统计值，而count可传入需要统计的字段名
	 *
	 * @param string => where condition
	 * @return int => total record counts
	 */
	final public function Total($where = '') {
		if(!empty($where)){
			$this->Where($where);
		}

		$data = $this->Field('COUNT(*) AS `total`')->SelectOne();
		return $data['total'];
	}




	/**
	 * Update record(s)
	 *
	 * @param array  => $maps = array('field1'=>value1, 'field2'=>value2, 'field3'=>value3))
	 * @param string => where condition
	 * @param boolean $self => self field ?
	 * @return false on failure or affected rows on success
	 */
	final public function Update($maps, $where = '', $self = FALSE) {
		$where = $this->_getWhere($where);
		$table = $this->parseTable();

		if (!$maps) {
			return false;
		} else {
			return DB::update($table, $maps, $where, $self);
		}
	}


	/**
	 * Delete record(s)
	 * @param string => where condition for deletion
	 * @return false on failure or affected rows on success
	 */
	final public function Delete($where = '') {
		$where = $this->_getWhere($where);
		$table = $this->parseTable();

		if(!$where){
			return FALSE;
		}

		return DB::delete($table, $where);
	}


	/**
	 * 返回上一次运行的SQL
	 * 
	 * @return type
	 */
	public function getLastSQL(){
		return $this->sql;
	}


	/**
	 * Execute special SELECT SQL statement
	 *
	 * @param string  => SQL statement for execution
	 */
	final public function Query($sql) {
		return DB::fetch_all($sql);
	}

	/**
	 * 加载数据
	 * 
	 * @todo rule未来可能抛弃
	 * @todo return计划抛弃，Create直接支持链式操作
	 * @todo 自动验证尚需要完善
	 * @param type $param
	 * @param type $rule
	 * @param type $mode	载入模式，1=>插入模式，2=>更新模式
	 * @param type $return		用于链式操作支持，默认为true不支持链式操作，仅返回数据，设置为false则返回自身并支持链式操作
	 * @return mixed			返回数据或类自身，默认返回数据
	 */
	final public function Create($param = array(), $rule = array(), $mode = 0, $return = true){
		if(empty($param) || !is_array($param)) {
			$param = $_POST;
		}
		
		if($mode == 0){//未设置模式
			//传入的数据是否存在主键，存在则为更新模式（$mode=2），否则为插入模式（$mode=1）
			$mode = isset($param[$this->_fieldsSetting['_pk']['field']]) ? 2 : 1;
		}

		//根据数据设置装载数据
		$data = $this->InitParam($param, $rule, $mode);

		//自动验证
		$validateRule = array();
		if(!empty($rule)){//如果传入的rule不为空，则根据rule过滤验证内容，过滤掉rule未设置的
			foreach($rule as $item){
				if(isset($this->_validate[$item])){
					$validateRule[] = $this->_validate[$item];
				}
			}
		} else {
			$validateRule = $this->_validate;
		}
//		$this->validate($data, $validateRule);

		if($this->_errNo != 0){//如果存在错误，则返回false
			return false;
		}

		$this->data = $data;
		return $return ? $data : $this;
	}

	/**
	 * 筛选数据
	 *
	 * @param type $param
	 */
	final protected function InitParam($param, $rule = array(), $mode = false, $filter = true) {
		$newParam = array();
		$default = $this->_default;
		if(empty($rule) || !is_array($rule)){
			$rule = $this->_fieldsSetting;
		}

		foreach($rule as $key => $val) {
			if(in_array((string)$key, array('_pk', '_default'))){//内置的定义则跳过
				continue;
			}
			//如果传过来的值中存在
			if(isset($param[$key])) {
				$value = $filter ? filter(stripSQLChars(stripHTML(trim($param[$key]), true))) : $param[$key];
				$newParam[$key] = $value;
			} elseif($mode == 1 && isset($default[$key])) {
				$newParam[$key] = $default[$key];
			} elseif($val['null'] == 'NO' && empty($val['default']) && $mode == 1){//该字段为必填项，而默认值为空，且当前是插入模式（更新模式不需要判断此项）
				$this->_needField[] = $key;
			}
		}

		$this->data = $newParam;
		return $newParam;
	}

	final protected function validate($param = array(), $rule = array()){
		$rule = is_array($rule) && !empty($rule) ? $rule : $this->_validate;

		foreach($rule as $key => $value){
			//如果规则中存在参数的字段，则验证
			if(isset($param[$key])){
				//调用私有方法传递需要验证的字段以及当前规则进行验证
				$this->_validate($param[$key], $value);
			}
		}

		//返回自身，支持链式操作
		return $this;
	}

	private function _validate($value, $rule){
		//如果empty规则存在且为true，则验证$value是否为空
		if(isset($rule['empty']) && $rule['empty'] && empty($value)){//如果empty为true，且value为空，则存储错误
			$this->_setError($rule['name'] .'不能为空', 2);		//必填字段为空是无害错误，允许执行update
		}

		$len = strlen($value);
		if(isset($rule['max']) && $len > $rule['max']){//如果value长度大于max，则存储错误
			$this->_setError($rule['name'] .'长度最长限制为'. $rule['max'], 1);
		}

		if(isset($rule['min']) && $len < $rule['min']){//如果value长度小于min，且当前value不为空，则存储错误
			$this->_setError($rule['name'] .'长度最小限制为'. $rule['min'], 1);
		}

		if(isset($rule['reg']) && !preg_match($rule['reg'], $value)){//如果存在正则验证，且验证失败，则存储错误
			$this->_setError($rule['name'] .'格式不正确'. $rule['max'], 1);
		}

		if(isset($rule['function'])){//如果存在方法验证，则调用
			//判断方法是否存在，方法名规则：validate_方法名
			$function = 'validate_'. $rule['function'];
			if(method_exists($this, $function)){
				$this->$function($value);
			}
		}
	}

	private function _setError($msg, $NO = 1){
		$this->_errNo = $NO;
		$this->_errMsg .= NL .$msg;
	}

	/**
	 * 修改模型名
	 *
	 * @param type $name
	 */
	public function setModelName($name = ''){
		$this->modelname = $name;
		$this->getTableName();

		return $this;
	}

	protected function getModelName(){
		$classname = get_called_class();
		$this->modelname = substr($classname,0,-5);
		return $this->modelname;
	}

	protected function getTableName($tableName = '') {
		if(empty($this->modelname)){
			$this->getModelName();
		}

		$oriTableName = $tableName ? $tableName : $this->modelname;
		$this->tablenameOri = $oriTableName;
		$this->tablename = DB::table($oriTableName);

		return $this->tablename;
	}

	/**
	 * 合并多个field定义，最终返回格式化后的字符串
	 * warning：暂未对多表联结支持，请勿使用本方法合并多表链接的参数
	 * 
	 * @param type $fields
	 * @return Boolean|string
	 */
	final protected function mergeField($fields){
		if(!is_array($fields)){
			return false;
		}

		foreach($fields as $key => $val){
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$fields[$key] = $this->parseField($val);
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
	final protected function mergeWhere($wheres){
		if(!is_array($wheres)){
			return false;
		}

		foreach($wheres as $key => $val){
			if(empty($val)){
				continue;
			}
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$where[$key] = $this->parseWhere($val);
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
	final protected function mergeOrder($orders){
		if(!is_array($orders)){
			return false;
		}

		foreach($orders as $key => $val){
			if(is_array($val)){//将参数中的数组格式化为字符串再拼接
				$orders[$key] = $this->parseOrder($val);
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
	final protected function parseOptionSQL($fieldsParam = '', $whereParam = ''){
		$fields = $where = $orderby = $limit = $join = '';
		if(isset($this->options['scope'])){//如果定义了命名范围，则调用方法处理

		}

		if(isset($this->options['fields'])){
			$fields = $this->options['fields'];
			unset($this->options['fields']);
		} elseif(!empty($fieldsParam)){
			$fields = $fieldsParam;
		}

		if(isset($this->options['where'])){
			$where = $this->options['where'];
			unset($this->options['where']);
		} elseif(!empty($whereParam)){
			$where = $whereParam;
		}

		if(isset($this->options['orderby'])){
			$orderby = $this->options['orderby'];
			unset($this->options['orderby']);
		}

		if(isset($this->options['limit'])){
			$limit = $this->options['limit'];
			unset($this->options['limit']);
		}

		if(isset($this->options['join'])){
			$join = $this->options['join'];
			unset($this->options['join']);
		}

		if(isset($this->options['group'])){
			$group = $this->options['group'];
			unset($this->options['group']);
		}

		return $this->parseSQL($fields, $where, $orderby, $limit, $join, $group);
	}

	/**
	 * 根据输入的参数生成SQL代码
	 * 
	 * @auth 小皓
	 * @date 2013-11-20
	 * @param type $field
	 * @param type $where
	 * @param type $orderby
	 * @param type $limit
	 * @param type $join
	 * @return string
	 */
	final protected function parseSQL($field = '', $where = '', $orderby = '', $limit = '', $join = array(), $group = ''){
		$joinBool = !empty($join);
		$sqlfield = $this->parseField($field, $joinBool);
		$sqlwhere = $this->parseWhere($where, $joinBool);
		$sqlorder = $this->parseOrder($orderby);
		$sqllimit = $this->parseLimit($limit);
		$sqlgroup = $this->parseGroup($group);
		
		//合并由Search方法传入的搜索条件与Where传入的查询条件
		if(isset($this->options['search'])){
			$sqlwhere = $this->parseSearch($sqlwhere);
		}
		
		
		if(isset($this->options['scope'])){
			file_put_contents(TMP_PATH .'/a.txt', $this->options['scope']);
			$scope = $this->parseScope();
			
			$sqlfield = !empty($scope['field']) ? $this->mergeField($sqlfield, $scope['field']) : $sqlfield;
			$sqlwhere = !empty($scope['where']) ? $this->mergeWhere($sqlwhere, $scope['where']) : $sqlwhere;
			$sqlorder = !empty($scope['order']) ? $this->mergeOrder($sqlorder, $scope['order']) : $sqlorder;
		}

		$sqltable = $this->parseTable($join);

		$sql = 'SELECT '. $sqlfield .' FROM '. $sqltable .' '. $sqlwhere .' '. $sqlgroup .' '. $sqlorder .' '. $sqllimit;
		$this->totalSql = 'SELECT COUNT(*) AS `count` FROM '. $sqltable .' '. $sqlwhere;

		return $sql;
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
	final protected function parseField($field, $join = false){
		$sqlfield = '';
		if(is_array($field)){
			$sqlfield = $this->_parseField($field, $join);
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
	private function _parseField($field, $alias = ''){
		$sqlfield = '';
		foreach($field as $key => $val){
			if($alias === true && is_string($key) && is_array($val)){//如果是多表联结，且$key为字符串$val为数组，则以key为别名继续拼装
				$sqlfield .= $this->_parseField($val, $key);		//递归调用自身
			} elseif(is_string($alias)) {
				$sqlfield .= $this->parseKey($val, $alias) .',';
			} else {
				$sqlfield .= $this->parseKey($val) .',';
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
	protected function parseScope(){
		$scope = $this->options['scope'];
		unset($this->options['scope']);
		$return = $data = array();

		if(!is_array($scope)){
			return array();
		}

		$isList = array_reduce(array_map('is_numeric', array_keys($scope)), 'and', true);
		if($isList){
			foreach($scope as $item){
				$sqlArr = $this->_parseScope($item);
				$data['field'][] = $sqlArr['field'];
				$data['where'][] = $sqlArr['where'];
				$data['order'][] = $sqlArr['order'];
			}
			
			$this->mergeField($data['field']);
			$this->mergeWhere($data['where']);
			$this->mergeOrder($data['order']);
		} else {
			$this->_parseScope($scope);
		}
		return $return;
	}
	
	private function _parseScope($scope){
		//可用于命名范围合并的内容
		$actionArr = array('field', 'where', 'order');
		$return = array();

		foreach($actionArr as $item){
			if(isset($scope[$item])){
				if(is_array($scope[$item]) && isset($this->options['alias'])){
					$scope[$item][$this->options['alias']] = $scope[$item];
				}
				$functionName = 'parse'. ucfirst($item);
				$return[$item] = $this->options[$item] = call_user_func(
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
	 * @return string
	 */
	protected function parseWhere($where){
		if(is_numeric($where)){//如果where是纯数字，则将其作为主键(id)来查询
			$pk = $this->_fieldsSetting['_pk']['field'];
			if(isset($this->options['alias'])){
				$pk = $this->options['alias'] .'.'. $pk;
			}
			$sqlwhere = $this->_parseWhere(array($pk => $where));
		} elseif(is_string($where)){
			$sqlwhere = $where;
		} else {
			$sqlwhere = $this->_parseWhere($where);
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
	private function _parseWhere($where, $alias = ''){
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
					$sqlwhere .= $this->_parseMultiWhere($key, $val, $alias, $operator);
				} elseif(is_string($key)){//如果key为字符串，则拼接为$key=$val的格式
					$key = $this->parseKey($key, $alias);
					if($operator == 'lr'){
						$sqlwhere .= $key .' LIKE \''. $val .'%\'';
					} elseif($operator == 'll'){
						$sqlwhere .= $key .' LIKE \'%'. $val .'\'';
					} elseif($operator == 'l'){
						$sqlwhere .= $key .' LIKE \'%'. $val .'%\'';
					} else {
						$sqlwhere .= $key . $operator . $this->parseValue($val);
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
	private function _parseMultiWhere($field, $value, $alias = '', $op = '='){
		if(is_string($field)){//$val为数组且$key为字符串，则拼接为field IN ('1', '2', '3')的格式
			$valueList = implode('\',\'', $value);

			$operator = $op == '=' ? 'IN' : 'NOT IN';
			$sqlwhere = $this->parseKey($field, $alias) ." {$operator} ('{$valueList}')";
		} else {
			$sqlwhere = $this->_parseWhere($value, $alias);
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
	final protected function parseSearch($oldwhere){
		$search = $this->options['search'];
		unset($this->options['search']);

		$where = array();
		foreach($search as $key => $val){
			$field = $this->_fieldsSetting[$key];
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
		
		if(isset($this->options['alias'])){
			$where = array(
				$this->options['alias'] => $where
			);
		}
		
		return ' WHERE '. $this->mergeWhere(array($oldwhere, $where));
	}

	/**
	 * 格式化group后的内容
	 *
	 * @auth 小皓
	 * @date 2014-6-8
	 * @param type $group
	 */
	final protected function parseGroup($group){
		if(is_array($group)){
			$group = 'GROUP BY '. $this->_parseGroup($group);
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
	private function _parseGroup($group, $alias = ''){
		$sqlgroup = '';
		foreach($group as $key => $val){
			if(is_string($val)) {
				$sqlgroup .= $this->parseKey($val);
			} elseif(is_array($val) && is_string($key)){
				$sqlgroup .= $this->_parseField($val, $key) .',';
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
	final protected function parseOrder($orderby) {
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
	final protected function parseLimit($limit){
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
	final protected function parseTable($join = array()){
		//此处用于跨库查询支持
		//跨库查询使用Database方法或$_database属性设置，如果两者存在，优先使用Database传入的库名
		$database = '';
		if(isset($this->options['database'])){
			$database = $this->parseField($this->options['database']) .'.';
			unset($this->options['database']);
		} elseif(!empty($this->_database)) {//设置了$_database属性
			$database = $this->parseField($this->_database) .'.';
		}

		$sqltable = $database . $this->table;
		if(isset($this->options['alias'])){
			$sqltable .= ' AS '. $this->options['alias'];
			unset($this->options['alias']);
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

	// Return true if last operation is success or false on failure
	public function getOperationFlag(){
		return $this->success;
	}


	// ********* Execute transaction ********* //
	/**
	 * Start a transaction
	 *
	 * @param null
	 * @return true on success or false on failure
	 */
	public function beginTransaction() {
		DB::beginTransaction();
	}


	/**
	 * Commit a transaction
	 *
	 * @param null
	 * @return true on success or false on failure
	 */
	public function Commit() {
		DB::commit();
	}


	/**
	 * Rollback a transaction
	 *
	 * @param  null
	 * @return true on success or false on failure
	 */
	public function Rollback() {
		DB::rollBack();
	}

	// *************** End ***************** //


	/**
	 * Close connection
	 *
	 * @param null
	 * @return null
	 */
	private function Close() {
//		self::$conn = null;
	}


	/**
	 * Destructor
	 *
	 * @param null
	 * @return null
	 */
	function __destruct() {
		$this->Close();
	}


	/**
	 * 字段和表名添加`
	 * 保证指令中使用关键字不出错 针对mysql
	 * @access protected
	 * @param mixed $value
	 * @return mixed
	 */
	protected function parseKey(&$value, $alias = '') {
		$value   =  trim($value);
		if( false !== strpos($value,' ') || false !== strpos($value,',') || false !== strpos($value,'*') ||  false !== strpos($value,'(') || false !== strpos($value,'.') || false !== strpos($value,'`')) {
			//如果包含* 或者 使用了sql方法 则不作处理
		} else {
			$value = '`'.$value.'`';
			if(!empty($alias) && is_string($alias)){
				$value = $alias .'.'. $value;
			}
		}
		return $value;
	}


	/**
	 * value分析
	 * @access protected
	 * @param mixed $value
	 * @return string
	 */
	protected function parseValue($value) {
//        if(is_string($value)) {
//            $value = '\''.$value.'\'';
//        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
//            $value   =  $value[1];
//        }elseif(is_null($value)){
//            $value   =  'null';
//        } elseif(empty($value)) {
//			
//		}
		return $value = '\''.$value.'\'';;
	}


	//扩展方法:
	public function SelectByID($field = '', $id){
		return $this->Field($field)->Where($id)->SelectOne();
	}

	/**
	 * 根据ID更新某一条记录
	 *
	 * @param $maps
	 * @param $id
	 * @return false
	 */
	public function UpdateByID($maps, $id){
		return $this->Where($id)->Update($maps);
	}

	public function DeleteByID($id){
		return $this->Where($id)->Delete();
	}

	public function getField($field = '', $where = array()){
		//如果field和where被传入，则将其传入链式中
		if($field){//有传入field
			$this->Field($field);
		}
		
		if($where){//有传入where
			$this->Where($where);
		}

		//本方法支持链式，field和where可以通过链式传入，因此需要判断一下是否通过链式获得field和where
		if(empty($this->options['fields']) || empty($this->options['where'])){//链式的field和where不存在，则出错
			unset($this->options);
			$this->options = array();
			return false;
		}

		$data = $this->SelectOne();

		return $data ? $data[$field] : false;
	}
	
	/**
	 * 从传入的where或链式传入的where中获得查询条件，并解析为可执行的SQL
	 * 
	 * @param type $where
	 * @return string
	 */
	private function _getWhere($where){
		if(isset($this->options['where'])){
			$where = $this->options['where'];
			unset($this->options['where']);
		}
		
		return $this->parseWhere($where);
	}

}