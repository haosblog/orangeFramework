<?php
/**
 * File: F_Uploadify.php
 * Functionality: uploadify的共用函数
 * Author: hao
 * Date: 2014-5-21 11:45:41
 */

/**
 * 
 * 
 * @param type $path		保存的文件路径
 * @param type $fileTypes	允许的文件类型，为空则默认为图片
 */
function uploadify($path, $fileTypes = array()){
	Helper::import('File');

	if (!empty($_FILES)) {
		$tempFile = $_FILES['Filedata']['tmp_name'];
		$targetPath = UPLOAD_PATH. '/'. $path;   // 保存文件的目标文件夹

		if(!file_exists($targetPath)){
			createRDir($targetPath);
		}

		// Validate the file type
		$fileTypes = empty($fileTypes) ? array('jpg', 'jpeg', 'gif', 'png') : $fileTypes;
		$fileParts = pathinfo($_FILES['Filedata']['name']);

		$name  = uniqid(). '.' . $fileParts['extension'];
		$targetFile = $targetPath . $name;
		if (in_array(strtolower($fileParts['extension']), $fileTypes)) {
			move_uploaded_file($tempFile, $targetFile);

			return IMG_DOMAIN.'/'. $path . $name;
		} else {
			return 'error when uploading file !';
		}
	}
}