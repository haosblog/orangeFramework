<?php
/**
 * File: Smarty_config.php
 * Functionality: Smarty config file
 * Author: Nic XIE
 * Date: 2011-12-31
 * Remark: REMEMBER set UAT to FALSE in PRODUCTION !
 * Note: DO NOT MODIFY THIS FILE UNLESS YOU FULLY UNDERSTAND !
 */

$smarty = new smarty;

// Template directory
if(defined('ADMIN_MODE')){
	// Don't cache in Admin case
	$smarty->caching  = false;
	$smarty->template_dir = ADMIN_PATH.'/view/';
} else {
	// Website
	$template_dir = BASE_PATH.'/view/';

	$smarty->template_dir = $template_dir;

	if(ENVIRONMENT != 'DEV'){
		// Set cache and dir:
		$smarty->caching = true;
		$smarty->cache_dir = CACHE_PATH;

		// 1 minutes
		$smarty->cache_lifetime = 60;
	}
}

$smarty->compile_dir = CMP_PATH;

// Delimiter
$smarty->left_delimiter  = "<{";
$smarty->right_delimiter = "}>";

// force_compile IN UAT and Admin, false IN PRODUCTION !
if(ENVIRONMENT == 'DEV' || defined('ADMIN_MODE')){
	$smarty->force_compile = true;
}else{
	$smarty->force_compile = false;
}