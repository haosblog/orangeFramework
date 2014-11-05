<?php 
/**
 *  测试程序执行时间
 */ 

class L_Timer {

	private $StartTime = 0;   
	private $StopTime = 0;   
	private $TimeSpent = 0;   
	  
	function __construct() {
		
	}
	
	public function start(){
		$this->StartTime = microtime();   
	}
	  
	public function stop(){
		$this->StopTime = microtime();   
	}
	
	public function spent() {
		if($this->TimeSpent) {
			return $this->TimeSpent;
		} else {
			$StartMicro  = substr($this->StartTime, 0, 10);
			$StartSecond = substr($this->StartTime, 11, 10);
			$StopMicro   = substr($this->StopTime, 0, 10);
			$StopSecond  = substr($this->StopTime, 11,10);
			$start       = floatval($StartMicro) + $StartSecond;
			$stop        = floatval($StopMicro)  + $StopSecond;
			$this->TimeSpent = $stop - $start; 
			return round($this->TimeSpent, 8).'秒';
		}
	}
}


//测试代码  
//$GLOBALS['l_timer'] = new L_Timer;
//$GLOBALS['l_timer']->start();
//$GLOBALS['l_timer']->stop();  
//echo '</br>运行时间为: '.$GLOBALS['l_timer']->spent();  
//unset($GLOBALS['l_timer']);  
