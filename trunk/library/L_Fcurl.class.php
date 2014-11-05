<?php
/**
* File:L_Fcurl.class.php
* Functionality:curl 
*/

class Fcurl {
	// url
	const TASK_ITEM_URL = 0;
	
	// file pointer
	const TASK_ITEM_FP = 1;
	
	// arguments
	const TASK_ITEM_ARGS = 2;
	
	// operation, task level
	const TASK_ITEM_OPT = 3;
	
	// success callback
	const TASK_PROCESS = 4;
	
	// curl fail callback
	const TASK_FAIL = 5;
	
	// tryed times
	const TASK_TRYED = 6;
	
	// thread limit
	public $limit = 30;
	
	// try time(s) before curl failed
	public $maxTry = 3;
	
	// operation, class level
	public $opt = array ();
	
	// cache options
	public $cache = array (
		'on' => false,
		'dir' => null,
		'expire' => 86400 
	);
	
	// task callback,if taskpool is empty,this callback will be called,you can call CUrl::add() in callback, $task[0] is callback, $task[1] is args for callback
	public $task = null;
	
	// show status or not
	public $showStatus = true;
	
	// taskpool is dynamic ?
	private $dynamicTask = true;
	
	// the real multi-thread num
	private $activeNum = 0;
	
	// finished task in the queue
	private $queueNum = 0;
	
	// finished task number,include failed task and cache
	private $finishNum = 0;
	
	// The number of cache hit
	private $cacheNum = 0;
	
	// completely failed task number
	private $failedNum = 0;
	
	// task num has added
	private $taskNum = 0;
	
	// all added task was saved here first
	private $taskPool = array ();
	
	// running task(s)
	private $taskRunning = array ();
	
	// failed task need to retry
	private $taskFailed = array ();
	
	// total downloaded size,byte
	private $traffic = 0;
	
	// handle of multi-thread curl
	private $mh = null;
	
	// time multi-thread start
	private $startTime = null;
	
	// curl multithread is running
	private $running = false;
	
	/**
	 * running infomation
	 */
	function status() {
		static $last = 0;
		static $strlen = 0;
		$now = time ();
		
		// update status every 1 minute or all task finished
		if ($now > $last or ($this->finishNum == $this->taskNum)) {
			$last = $now;
			$timeSpent = $now - $this->startTime;
			if ($timeSpent == 0) {
				$timeSpent = 1;
			}
			
			$s = '';
			if (! $this->dynamicTask) {
				// percent
				$s .= sprintf ( '%-.2f%%  ', round ( $this->finishNum / $this->taskNum, 4 ) * 100 );
			}
			
			// active, queueNum
			$s .= sprintf ( '%' . strlen ( $this->activeNum ) . 'd/%-' . strlen ( $this->queueNum ) . 'd', $this->activeNum, $this->queueNum );
			
			// finished, cacheNum, taskNum, failedNum
			$s .= sprintf ( '  %' . strlen ( $this->finishNum ) . 'd(%-' . strlen ( $this->cacheNum ) . 'd)/%-' . strlen ( $this->taskNum ) . 'd/%-' . strlen ( $this->failedNum ) . 'd', $this->finishNum, $this->cacheNum, $this->taskNum, $this->failedNum );
			
			// speed
			$speed = ($this->finishNum - $this->cacheNum) / $timeSpent;
			$s .= sprintf ( '  %-d', $speed ) . '/s';
			
			// net speed
			$suffix = 'KB';
			$netSpeed = $this->traffic / 1024 / $timeSpent;
			if ($netSpeed > 1024) {
				$suffix = 'MB';
				$netSpeed /= 1024;
			}
			
			$s .= sprintf ( '  %-.2f' . $suffix . '/s', $netSpeed );
			
			// total size
			$suffix = 'KB';
			$size = $this->traffic / 1024;
			if ($size > 1024) {
				$suffix = 'MB';
				$size /= 1024;
				if ($size > 1024) {
					$suffix = 'GB';
					$size /= 1024;
				}
			}
			
			$s .= sprintf ( '  %-.2f' . $suffix, $size );
			if (! $this->dynamicTask) {
				// estimated time of arrival
				if ($speed == 0) {
					$str = '--';
				} else {
					$eta = ($this->taskNum - $this->finishNum) / $speed;
					$str = ceil ( $eta ) . 's';
					if ($eta > 3600) {
						$str = ceil ( $eta / 3600 ) . 'h' . ceil ( ($eta % 3600) / 60 ) . 'm';
					} elseif ($eta > 60) {
						$str = ceil ( $eta / 60 ) . 'm' . ($eta % 60) . 's';
					}
				}
				$s .= '  ETA ' . $str;
			}
			$len = strlen ( $s );

			if ($len > $strlen) {
				$strlen = $len;
			} else {
				$t = $strlen - $len;
				// clean right surplus characters and back
				//echo str_pad ( '', $t ) . str_repeat ( chr ( 8 ), $t );
			}
			
			if ($this->finishNum == $this->taskNum)
				echo "\n";
		}
	}
	
	/**
	 * single thread download, curl can create the last level directory
	 *
	 * @param string $url           
	 * @param string $file          
	 * @return boolean
	 */
	function download($url, $file) {
		$ch = $this->init ($url, $file);
		$dir = dirname ($file);
		if (! file_exists ($dir))
			mkdir ($dir, 0755);
		$fp = fopen($file, 'w');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$r = curl_exec($ch);
		fclose($fp);
		if (curl_errno($ch) !== 0) {
			echo 'curl error ' . curl_errno ( $ch ) . " : " . curl_error ( $ch ) . "\n";
		}
		curl_close($ch);
		return $r;
	}
	
	/**
	 * single thread
	 *
	 * @param string $url           
	 * @param array $opt            
	 * @return mixed false if fail, array('info'=>array(),'content'=>'') if success
	 */
	function read($url, $opt = array()) {
		if ($this->cache ['on']) {
			$r = $this->cache ( $url );
			if (null !== $r)
				return $r;
		}
		
		$r = array ();
		$ch = $this->init ( $url );
		if (! empty ( $opt )) {
			foreach ( $opt as $k => $v ) {
				curl_setopt ( $ch, $k, $v );
			}
		}
		
		$content = curl_exec ( $ch );
		if (curl_errno ( $ch ) === 0) {
			$r ['info'] = curl_getinfo ( $ch );
			$r ['content'] = $content;
			if ($this->cache ['on'])
				$this->cache ( $url, $r );
		} else {
			echo 'curl error ' . curl_errno ( $ch ) . " : " . curl_error ( $ch ) . "\n";
		}
		curl_close ( $ch );
		return $r;
	}
	
	/**
	 * add a task to taskPool
	 *
	 * @param array $item
	 *              array('url'=>''[,'file'=>''][,'opt'=>array()][,'args'=>array()]
	 * @param mixed $process
	 *              success callback, first param array('info'=>,'content'=>), second param $item[args]
	 * @param mixed $fail
	 *              curl fail callback, first param array('error_no'=>,'error_msg'=>,'url'=>);
	 *              
	 * @return boolean
	 */
	function add($item, $process = null, $fail = null) {
		$r = false;
		// check
		if (! is_array ( $item )) {
			user_error ( 'item must be array, item is ' . gettype ( $item ), E_USER_WARNING );
		}
		
		$item ['url'] = trim ( $item ['url'] );
		if (empty ( $item ['url'] )) {
			user_error ( "url can't be empty, url=$item[url]", E_USER_WARNING );
		} else {
			// replace space with + to avoid curl problems
			$item ['url'] = str_replace ( ' ', '+', $item ['url'] );
			
			// fix
			if (empty ( $item ['file'] ))
				$item ['file'] = null;
			if (empty ( $item ['opt'] ))
				$item ['opt'] = array ();
			if (empty ( $item ['args'] ))
				$item ['args'] = array ();
			if (empty ( $process )) {
				$process = null;
			}
			
			if (empty ( $fail )) {
				$fail = null;
			}
			$task = array ();
			$task [self::TASK_ITEM_URL] = $item ['url'];
			$task [self::TASK_ITEM_FP] = $item ['file'];
			$task [self::TASK_ITEM_ARGS] = array ($item ['args'] );
			$task [self::TASK_ITEM_OPT] = $item ['opt'];
			$task [self::TASK_PROCESS] = $process;
			$task [self::TASK_FAIL] = $fail;
			$task [self::TASK_TRYED] = 0;
			$this->taskPool [] = $task;
			$this->taskNum ++;
			$r = true;
		}
		return $r;
	}
	
	/**
	 * Perform the actual task(s).
	 */
	function go() {
		if ($this->running)
			user_error ( 'CURL can only run one instance', E_USER_ERROR );
		$this->mh = curl_multi_init ();
		$this->addTask ();
		$this->startTime = time ();
		$this->running = true;
		do {
			$this->exec ();
			
			// curl_multi_select mainly used for blocking
			curl_multi_select ( $this->mh );
			while ( false != ($curlInfo = curl_multi_info_read ( $this->mh, $this->queueNum )) ) {
				$ch = $curlInfo ['handle'];
				$info = curl_getinfo ( $ch );
				$this->traffic += $info ['size_download'];
				$task = $this->taskRunning [$ch];
				if (empty ( $task )) {
					user_error ( "can't get running task", E_USER_WARNING );
				}
				
				$callFail = false;
				if ($curlInfo ['result'] == CURLE_OK) {
					if (isset ( $task [self::TASK_PROCESS] )) {
						$param = array ();
						$param ['info'] = $info;
						if (! isset ( $task [self::TASK_ITEM_FP] ))
							$param ['content'] = curl_multi_getcontent ( $ch );
						array_unshift ( $task [self::TASK_ITEM_ARGS], $param );
					}
					
					// write cache
					if ($this->cache ['on'] and ! isset ( $task [self::TASK_ITEM_FP] ))
						$this->cache ( $task [self::TASK_ITEM_URL], $param );
				} else {
					if ($task [self::TASK_TRYED] >= $this->maxTry) {
						$err = array (
							'error_no' => $curlInfo ['result'],
							'error_msg' => curl_error ( $ch ),
							'url' => $info ['url'] 
						);
						
						if (isset ( $task [self::TASK_FAIL] )) {
							array_unshift ( $task [self::TASK_ITEM_ARGS], $err );
							$callFail = true;
						} else {
							echo 'Curl Error ' . implode ( ', ', $err ) . "\n";
						}
						$this->failedNum ++;
					} else {
						$task [self::TASK_TRYED] ++;
						$this->taskFailed [] = $task;
						$this->taskNum ++;
					}
				}
				
				curl_multi_remove_handle ( $this->mh, $ch );
				curl_close ( $ch );
				if (isset ( $task [self::TASK_ITEM_FP] ))
					fclose ( $task [self::TASK_ITEM_FP] );
						
				//print_r($task [self::TASK_ITEM_ARGS]);die;
				if ($curlInfo ['result'] == CURLE_OK) {
					//call_user_func_array ( $task [self::TASK_PROCESS], $task [self::TASK_ITEM_ARGS] );
					call_user_func_array ( $task [self::TASK_PROCESS][0],$task [self::TASK_PROCESS][1] );
				} elseif ($callFail) {
					call_user_func_array ( $task [self::TASK_FAIL], $task [self::TASK_ITEM_ARGS] );
				}
				
				unset ( $this->taskRunning [$ch] );
				$this->finishNum ++;
				$this->addTask ();
				
				// so skilful,if $this->queueNum grow very fast there will be no efficiency lost,because outer $this->exec() won't be executed.
				$this->exec ();
				if ($this->showStatus) {
					$this->status ();
				}
			}
		} while ( $this->activeNum || $this->queueNum || ! empty ( $this->taskFailed ) || ! empty ( $this->taskRunning ) || ! empty ( $this->taskPool ) );
		
		curl_multi_close ( $this->mh );
		unset ( $this->startTime, $this->mh );
		$this->cacheNum = 0;
		$this->finishNum = 0;
		$this->taskNum = 0;
		$this->traffic = 0;
		$this->running = false;
	}
	
	/**
	 * curl_multi_exec()
	 */
	private function exec() {
		while ( curl_multi_exec ( $this->mh, $this->activeNum ) === CURLM_CALL_MULTI_PERFORM ) {
			}
	}
	
	/**
	 * add a task to curl, keep $this->limit concurrent automatically
	 */
	private function addTask() {
		$c = $this->limit - count ( $this->taskRunning );
		while ( $c > 0 ) {
			$task = array ();
			// search failed first
			if (! empty ( $this->taskFailed )) {
				$task = array_pop ( $this->taskFailed );
			} else {
				if (0 < ( int ) ($this->limit - count ( $this->taskPool )) and ! empty ( $this->task )) {
					call_user_func_array ( $this->task [0], array ($this->task [1] ) );
				}
				if (! empty ( $this->taskPool ))
					$task = array_pop ( $this->taskPool );
			}
			
			$cache = null;
			if (! empty ( $task )) {
				if ($this->cache ['on'] == true and ! isset ( $task [self::TASK_ITEM_FP] )) {
					$cache = $this->cache ( $task [self::TASK_ITEM_URL] );
					if (null !== $cache) {
						array_unshift ( $task [self::TASK_ITEM_ARGS], $cache );
						$this->finishNum ++;
						$this->cacheNum ++;
						call_user_func_array ( $task [self::TASK_PROCESS], $task [self::TASK_ITEM_ARGS] );
					}
				}
				
				if (! $cache) {
					$ch = $this->init ( $task [self::TASK_ITEM_URL] );
					if (is_resource ( $ch )) {
						// is a download task?
						if (isset ( $task [self::TASK_ITEM_FP] )) {
							// curl can create the last level directory
							$dir = dirname ( $task [self::TASK_ITEM_FP] );
							if (! file_exists ( $dir ))
								mkdir ( $dir, 0777 );
							$task [self::TASK_ITEM_FP] = fopen ( $task [self::TASK_ITEM_FP], 'w' );
							curl_setopt ( $ch, CURLOPT_FILE, $task [self::TASK_ITEM_FP] );
						}
						
						// single task curl option
						if (isset ( $task [self::TASK_ITEM_OPT] )) {
							foreach ( $task [self::TASK_ITEM_OPT] as $k => $v ) {
								curl_setopt ( $ch, $k, $v );
							}
						}
						
						curl_multi_add_handle ( $this->mh, $ch );
						$this->taskRunning [$ch] = $task;
					} else {
						user_error ( '$ch is not resource,curl_init failed.', E_USER_WARNING );
					}
				}
			}
			if (! $cache)
				$c --;
		}
	}
	
	/**
	 * set or get file cache
	 *
	 * @param string $url           
	 * @param mixed $content
	 *              null represent get a cache
	 * @return return read:content or false, write: true or false
	 */
	private function cache($url, $content = null) {
		$key = md5 ( $url );
		if (! isset ( $this->cache ['dir'] ))
			user_error ( 'Cache dir is not defined', E_USER_ERROR );
		$dir = $this->cache ['dir'] . DIRECTORY_SEPARATOR . substr ( $key, 0, 3 );
		$file = $dir . DIRECTORY_SEPARATOR . substr ( $key, 3 );
		if (! isset ( $content )) {
			if (file_exists ( $file )) {
				if ((time () - filemtime ( $file )) < $this->cache ['expire']) {
					return unserialize ( file_get_contents ( $file ) );
				} else {
					unlink ( $file );
				}
			}
		} else {
			$r = false;
			// check main cache directory
			if (! is_dir ( $this->cache ['dir'] )) {
				user_error ( "Cache dir doesn't exists", E_USER_ERROR );
			} else {
				$dir = dirname ( $file );
				if (! file_exists ( $dir ) and ! mkdir ( $dir, 0777 ))
					user_error ( "Create dir failed", E_USER_WARNING );
				$content = serialize ( $content );
				if (file_put_contents ( $file, $content, LOCK_EX ))
					$r = true;
				else
					user_error ( 'Write cache file failed', E_USER_WARNING );
			}
			return $r;
		}
	}
	
	/**
	 * get curl handle
	 *
	 * @param string $url           
	 * @return resource
	 */
	private function init($url) {
		$ch = curl_init ();
		$opt = array ();
		$opt [CURLOPT_URL] = $url;
		$opt [CURLOPT_HEADER] = false;
		$opt [CURLOPT_CONNECTTIMEOUT] = 100000000;
		$opt [CURLOPT_TIMEOUT] = 300000000;
		$opt [CURLOPT_AUTOREFERER] = true;
		$opt [CURLOPT_USERAGENT] = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11';
		$opt [CURLOPT_RETURNTRANSFER] = true;
		$opt [CURLOPT_FOLLOWLOCATION] = true;
		$opt [CURLOPT_MAXREDIRS] = 10;
		// user defined opt
		if (! empty ( $this->opt ))
			foreach ( $this->opt as $k => $v )
				$opt [$k] = $v;
		curl_setopt_array ( $ch, $opt );
		return $ch;
	}
}

?>