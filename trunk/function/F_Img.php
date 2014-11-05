<?php
/*
 *  File: F_Img.php
 *  Functionality: Extra img functions
 *  Author: Nic XIE
 *  Date: 2013-01-11
 */
 
/*
 *  Create thumb
 *  @access public
 *  $sourceImg: source image
 *  $destination: 保存的目标路径
 *  $saveName: 保存的新图片名
 *  $targetWidth  缩略图宽度
 *  $targetHeight 缩略图高度
 *  @return full path + file name if success 
 *  Remark: 该函数使用的 getExtension 与 createRDir 存在于 F_File.php 中
 */
function createThumb($source, $destination, $saveName, $targetWidth, $targetHeight){
	// Get image size
	$originalSize = getimagesize($source);
	
	// Set thumb image size
	$targetSize = setWidthHeight($originalSize[0], $originalSize[1], $targetWidth, $targetHeight);
	
	// Get image extension
	$ext = getExtension($source);
	
	// Determine source image type
	if($ext == 'gif'){
		$src = imagecreatefromgif($source);
	}elseif($ext == 'png'){
		$src = imagecreatefrompng($source);
	}elseif ($ext == 'jpg' || $ext == 'jpeg'){
		$src = imagecreatefromjpeg($source);
	}else{
		return 'Unknow image type !';
	}
	
	// Copy image
	$dst = imagecreatetruecolor($targetSize[0], $targetSize[1]);
	imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetSize[0], $targetSize[1],$originalSize[0], $originalSize[1]);    
	
	if(!file_exists($destination)){
		if(!createRDir($destination)){
			return 'Unabled to create destination folder !';
		}
	}
	
	// destination + fileName
	$thumbName = $destination.'/'.$saveName.'.'.$ext;
	
	if($ext == 'gif'){
		imagegif($dst, $thumbName);
	}else if($ext == 'png'){
		imagepng($dst, $thumbName);
	}else if($ext == 'jpg' || $ext == 'jpeg'){
		imagejpeg($dst, $thumbName, 100);
	}else{
		return 'Fail to create thumb !';
	}
	
	imagedestroy($dst);
	imagedestroy($src);
	return $thumbName;
}


/*
 *  Set thumb image width and height
 */
function setWidthHeight($width, $height, $maxWidth, $maxHeight) {
	if($width > $height){
		if($width > $maxWidth){
			$difinwidth = $width/$maxWidth;
			$height = intval($height/$difinwidth);
			$width  = $maxWidth;
			
			if($height > $maxHeight){
				$difinheight = $height/$maxHeight;
				$width  = intval($width/$difinheight);
				$height = $maxHeight;
			}
		}else{
			if($height > $maxHeight){
				$difinheight = $height/$maxHeight;
				$width  = intval($width/$difinheight);
				$height = $maxHeight;
			}
		}
	}else{
		if($height > $maxHeight){
			$difinheight = $height/$maxHeight;
			$width  = intval($width/$difinheight);
			$height = $maxHeight;
			
			if($width > $maxWidth){
				$difinwidth = $width/$maxWidth;
				$height = intval($height/$difinwidth);
				$width  = $maxWidth;
			}
		}else{
			if($width > $maxWidth){
				$difinwidth = $width/$maxWidth;
				$height = intval($height/$difinwidth);
				$width  = $maxWidth;
			}
		}
	}
	
	$final = array($width, $height);
	return $final;
}

/**
 * 取得图像信息
 * @public
 * @access public
 * @param string $image 图像文件名
 * @return mixed
 */
function getImageInfo($img) {
	$imageInfo = getimagesize($img);
	if ($imageInfo !== false) {
		$imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
		$imageSize = filesize($img);
		$info = array(
			"width" => $imageInfo[0],
			"height" => $imageInfo[1],
			"type" => $imageType,
			"size" => $imageSize,
			"mime" => $imageInfo['mime']
		);
		return $info;
	} else {
		return false;
	}
}

/**
 * [imagecopymerge_alpha 图片复制的方法，先copy再merge，实现通明度]
 * @param  [type] $dst_im [目标图片]
 * @param  [type] $src_im [源图片]
 * @param  [type] $dst_x  [目标地址x]
 * @param  [type] $dst_y  [目标地址y]
 * @param  [type] $src_x  [源地址x]
 * @param  [type] $src_y  [源地址y]
 * @param  [type] $src_w  [源的宽]
 * @param  [type] $src_h  [源的高]
 * @param  [type] $pct    [透明度   当 pct = 0 时，实际上什么也没做，它对真彩色图像实现了 alpha 透明]
 * @return [type]         [description]
 */
function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
	$opacity = $pct;
	// getting the watermark width
	$w = imagesx($src_im);
	// getting the watermark height
	$h = imagesy($src_im);
		
	// creating a cut resource
	$cut = imagecreatetruecolor($src_w, $src_h);
	// copying that section of the background to the cut
	imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
	// inverting the opacity
	//$opacity = 100 - $opacity;
		
	// placing the watermark now
	imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
	imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
}


/**
 * 为图片添加水印
 * @public public
 * @param string $source 原文件名
 * @param string $water  水印图片
 * @param string $$savename  添加水印后的图片名
 * @param string $alpha  水印的透明度
 * @return void
 */
function water($source, $water, $savename = null, $alpha = 80) {
	//检查文件是否存在
	if (!file_exists($source)){
		return false;
	}

	//图片信息
	$sInfo = getImageInfo($source);
	$wInfo = getImageInfo($water);

	//如果图片小于水印图片，不生成图片
	if ($sInfo["width"] < $wInfo["width"] || $sInfo['height'] < $wInfo['height']){
		//return false;
	}

	//建立图像
	if($sInfo['type'] === 'bmp'){
		$sCreateFun = "imagecreatefromw" . $sInfo['type'];
		$sImage = $sCreateFun($source);
	}else{
		$sCreateFun = "imagecreatefrom" . $sInfo['type'];
		$sImage = $sCreateFun($source);
	}
	
	if($wInfo['type'] === 'bmp'){
		$wCreateFun = "imagecreatefromw" . $wInfo['type'];
		$wImage = $wCreateFun($water);
	}else{
		$wCreateFun = "imagecreatefrom" . $wInfo['type'];
		$wImage = $wCreateFun($water);
	}

	//设定图像的混色模式
	imagealphablending($wImage, true);

	//图像位置,默认为右下角右对齐
	$posY = $sInfo["height"] - $wInfo["height"];
	$posX = $sInfo["width"] - $wInfo["width"];

	//生成混合图像
	imagecopymerge_alpha($sImage, $wImage, $posX, $posY, 0, 0, $wInfo['width'], $wInfo['height'], $alpha);

	//输出图像
	if($sInfo['type'] === 'bmp'){
		$ImageFun = 'Imagew' . $sInfo['type'];
	}else{
		$ImageFun = 'Image' . $sInfo['type'];
	}

	//如果没有给出保存文件名，默认为原图像名
	if (!$savename) {
		$savename = $source;
		@unlink($source);
	}

	//保存图像
	$ImageFun($sImage, $savename);
	imagedestroy($sImage);
}

/**
 * 图片加水印
 * @param  [type] $path [指定的路径]
 * @return [type]       [description]
 */
function addWaterMask($path) {
	if (!is_dir($path)){
		return false;
	}

	$handle = opendir($path);

	while (($file = readdir($handle)) !== false) {
		if ($file != '.' && $file != '..') {
			$path2 = $path . '/' . $file;
			if (is_dir($path2)) {
				addWaterMask($path2);
			} else {
				if(strpos($file, 'water_') === false){
					$waterName = $path.'/'.'water_'.$file;
					if(!file_exists($waterName)){
						water($path2, BASE_PATH.'/asset/watermark.png', $waterName, 80);
					}
				}
			}
		}
	}
}