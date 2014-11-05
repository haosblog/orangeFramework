<?php

/**
 * File: L_UploadFile.php
 * Functionality: 
 * Author: hao
 * Date: 2014-7-15 10:37:09
 */
class L_UploadFile {
	
	private $uploadPath;
	
	private $uploadName;
	
	private $fileObj;
	//允许上传的文件类型
	//默认仅支持图片格式
	private $allow = array(
		'image/pjpeg',
		'image/jpeg',
		'image/png',
		'image/gif'
	);
	
	private $filename;
	//文件类型
	private $fileType;
	//默认最大限制为2M
	private $maxsize = 2000;
	//禁止上传的文件类型黑名单
	private $blackList = array('php', 'exe', 'html', 'htm');
	
	public $error;
	//错误编码
	//	1 => 上传错误
	//	2 => 文件超出限制
	//	3 => 文件类型不正确
	//	4 => 没有上传
	public $errorno = 0;

	
	public function __construct($fileObjName, $uploadPath){
		Helper::import('File');

		$this->uploadPath = UPLOAD_PATH . $uploadPath;
		if(!is_dir($this->uploadPath)){
			createRDir($this->uploadPath);
		}
		
		$this->fileObj = $_FILES[$fileObjName];
		if ($this->fileObj["error"] > 0){
			$this->errorno = 1;
			$this->error = $_FILES["file"]["error"];
		}
	}
	
	/**
	 * 保存上传的文件
	 * 
	 * @return string
	 */
	public function save(){
		if($this->fileObj['size'] > $this->maxsize * 1024){
			$this->_setError(2);
		}

		if(!in_array($this->fileObj['type'], $this->allow)){
			$this->_setError(3);
		}

		if(!$this->fileObj['name']){
			$this->_setError(4);
		}

		if($this->errorno == 0){
			$this->filename = $this->_buildSaveName($this->fileObj['name']);
			$filepath = $this->uploadPath .'/'. $this->filename;

			move_uploaded_file($this->fileObj["tmp_name"], $filepath);

			return $filepath;
		}

		return $this->errorno;
	}

	public function setMaxSize($maxSize = 2000){
		$this->maxsize = $maxSize;
	}
	
	public function getFileName(){
		return $this->filename;
	}

	/**
	 * 设置上传错误
	 * 
	 *	1 => 上传错误
	 *	2 => 文件超出限制
	 *	3 => 文件类型不正确
	 *	4 => 没有上传
	 * @param type $errno
	 */
	private function _setError($errno){
		$this->errorno = $errno;
		switch ($errno){
			case 2:
				$this->error .= '文件大小超出范围';
				break;

			case 3:
				$this->error .= '您上传的文件不被允许';
				break;

			case 4:
				$this->error .= '请选择上传文件';
				break;

			default :
				$this->error .= '文件上传错误';
		}
	}

	private function _buildSaveName($oldname){
		$start = strripos($oldname, '.');
		$this->fileType = substr($oldname, $start + 1);

		$newName = uniqid() . '.'. $this->fileType;

		return $newName;
	}
}
