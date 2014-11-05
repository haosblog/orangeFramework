<?php

/**
 * File: F_Role.php
 * Functionality: 角色控制组件的快捷调用函数
 * Author: hao
 * Date: 2014-5-30 11:28:17
 */


/**
 * 检测用户权限
 * 
 * @auth 小皓
 * @param type $role
 * @return type
 */
function checkRole($role){
	$com_role = Helper::loadComponment('member/role');
	
	if(is_string($role)){
		$role = $com_role->getRoleNum($role);
	}
	
	return $com_role->checkRole(0, $role);
}

/**
 * 检测用户权限
 * 
 * @param type $purview
 */
function checkPurview($role, $purview){
	$com_role = Helper::loadComponment('member/role');
	
	return $com_role->checkPurview($role, $purview);
}