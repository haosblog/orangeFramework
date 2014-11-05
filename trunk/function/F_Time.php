<?php
/** 
 * File: F_Time.php
 * Functionality: Extra Time functions
 * Author: Fnatic Li
 * Date: 2012-11-12
 */

// 获取当前是周几，返回：sunday、monday..
function getWeekNow(){
	return strtolower(date('l'));			
}


/**
 *  Functionality:		 传入两个时间戳，返回一个相差的天数	
 *  @param int $n_time: 现在时间
 *  @param int $t_time: 目标时间
 *  @return: 			返回一个相差的天数
 */
function countDay($n_time,$t_time){
	$t_time = strtotime($t_time);
	return intval(($n_time - $t_time)/86400);
}


/**
 * 把时间转换成几秒前,几分钟前之类
 */
function time_tran($the_time, $suffix = ''){
	$now_time = date("Y-m-d H:i:s", CUR_TIMESTAMP);
	$now_time = strtotime($now_time);
	$show_time = $the_time;
	$dur = $now_time - $show_time;
   
	if($dur < 60){
   		$result = $dur . '秒';
	}elseif($dur <3600){
   		$result = floor($dur/60).'分钟';
	}elseif($dur < 86400){
   		$result = floor($dur/3600).'小时';
	}elseif($dur < 604800){
   		$result = floor($dur/86400).'天';
	}elseif($dur < 2592000){
   		$result = floor($dur/604800).'周';
	}elseif($dur < 31104000){
   		$result = floor($dur/2592000).'月';
	}elseif($dur > 31104000){
   		$result = floor($dur/31104000).'年';
	}
   
	return $result.$suffix;
}

/**
 * 获取半年内,3个月内等时间
 */
function get_period_time($type='day'){
    $rs = FALSE;
    $now = time();
    switch ($type){
        case 'day'://今天
            $rs['beginTime'] = date('Y-m-d 00:00:00', $now);
            $rs['endTime'] = date('Y-m-d 23:59:59', $now);
        break;

        case 'week'://本周
            $time = '1' == date('w') ? strtotime('Monday', $now) : strtotime('last Monday', $now);
            $rs['beginTime'] = date('Y-m-d 00:00:00', $time);
            $rs['endTime'] = date('Y-m-d 23:59:59', strtotime('Sunday', $now));
        break;

        case 'month'://本月
            $rs['beginTime'] = date('Y-m-d 00:00:00', mktime(0, 0, 0, date('m', $now), '1', date('Y', $now)));
            $rs['endTime'] = date('Y-m-d 23:39:59', mktime(0, 0, 0, date('m', $now), date('t', $now), date('Y', $now)));
            break;
        case '3month'://三个月
            $time = strtotime('-2 month', $now);
            $rs['beginTime'] = date('Y-m-d 00:00:00', mktime(0, 0,0, date('m', $time), 1, date('Y', $time)));
            $rs['endTime'] = date('Y-m-d 23:39:59', mktime(0, 0, 0, date('m', $now), date('t', $now), date('Y', $now)));
        break;

        case 'half_year'://半年内
            $beginTime = strtotime('-7 month', $now);
            $endTime = strtotime('-1 month', $now);
            $rs['beginTime'] = $beginTime;
            $rs['endTime'] = $endTime;
        break;

        case 'year'://今年内
            $rs['beginTime'] = date('Y-m-d 00:00:00', mktime(0, 0,0, 1, 1, date('Y', $now)));
            $rs['endTime'] = date('Y-m-d 23:39:59', mktime(0, 0, 0, 12, 31, date('Y', $now)));
        break;
    }
    return $rs;
}

/**
 * 后台会员列表时间转换函数
 */
function formatTime($time){
	return date('Y-m-d',$time);
}