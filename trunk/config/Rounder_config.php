<?php
/**
 * File: Rounder_config.php
 * Functionality: 路由相关配置
 * Author: hao
 * Date: 2014-9-30 9:36:37
 */

$Config['rounder'] = array(
	'project/:id(.html)' => '/xp/detail',
	'project/:regoin/:price(_):room(_):property(_):keyword(_):orderby(_):ajax(_):page' => '/project/index',
	'project/:price(_):room(_):property(_):keyword(_):orderby(_):ajax(_):page' => '/project/index',
);