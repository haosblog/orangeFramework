<?php
/**
 * File: Crond_config.php
 * Functionality: 定时执行控制器列表
 * Author: hao
 * Date: 2013-12-2
 */

/**
 * timer参数说明
 * weekday：代表每周几执行本定时器，0代表周日，为空或*则不限
 * day：每月哪一天执行本定时器，范围为1-31，为空或*则不限
 * hour：每天的哪一个小时执行本定时器，范围为0-23
 * minute：每小时的哪一分钟执行本定时器，范围0-59
 * timing：定时执行，单位为秒。此参数优先级最高
 */
$Config['crondList'] = array(
	array(
		'timer' => array('hour' => 23, 'minute' => 59),
		'crond' => 'updatePack'
	),
	array(
		'timer' => array('timing' => 60),
		'crond' => 'sms'
	),
	array(
		'timer' => array('timing' => 60),
		'crond' => 'updateGroupFee'
	),
	array(
		'timer' => array('hour' => 2, 'minute' => 00),
		'crond' => 'updateAgentSpecialist'
	),
);