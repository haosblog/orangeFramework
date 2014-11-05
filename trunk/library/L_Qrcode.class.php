<?php 
/**
 * File: L_Qrcode.class.php
 * Functionality: 生成二维码类（示例参考http://dev.513fdw.com/paul/createQr/ 文件：C_Paul.php）
 * Author: Paul
 * Date: 2013-08-7
 */

include LIB_PATH . "/qrcode/phpqrcode.php";
class L_Qrcode {
	/**
	 * $data string vcard的格式数据  
	 * for example :
	 * BEGIN:VCARD
	 * FN: 姓名
	 * TEL：电话号码  CELL：代表手机 VOICE 表示声音电话号码 WORK 表示工作电话号码
	 * EMAIL;TYPE=internet,pref:邮箱地址
	 * URL:地址  名片网址
	 * ORG:房道网  公司名称
	 * ROLE:PHP   职称
	 * TITLE:房道网技术部    职位
	 * ADR;WORK;POSTAL:广州市番禺区;520000  工作地址
	 * ADR;HOME;POSTAL:广州市天河区;520000  家庭住址
	 * REV:2013-08-07T14:30:02Z  名片修改时间
	 * $data = "BEGIN:VCARD
		FN:黄健
		TEL;CELL;VOICE:13268050058
		TEL;WORK;VOICE:020-00000000
		EMAIL;PREF;INTERNET:513fdw@513fdw.com
		URL:http://www.513fdw.com
		ORG:房道网
		ROLE:PHP
		TITLE:房道网技术部
		ADR;WORK;POSTAL:广州市番禺区;520000
		ADR;HOME;POSTAL:广州市天河区;520000
		REV:2013-08-07T14:30:02Z
		END:VCARD";
	 * */

	public $data;

	/**
	 * $fileName string/bool ：要保存的二维码图片路径，如果不用保存二维码图片则传入false
	 */
	public $fileName;

	/**
	 * $ecc string: 纠错级别：L、M、Q、H  默认为 L
	 */
	private $ecc;

	/**
	 * $size int：二维码 点的大小，1到10,默认为4 手机端扫描。
	 */
	private $size;

	/**
	 * $logo string：网站logo图片路径。
	 */
	private $logo;

	/**
	 * construct 构建方法
	 * @access public
	 * $data string： 二维码各项数据
	 * $fileName string/bool ：要保存的二维码图片路径，如果不用保存二维码图片则传入false
	 * $ecc string: 纠错级别：L、M、Q、H  默认为 L
	 * $size int：二维码 点的大小，1到10,默认为4 手机端扫描。
	 * $logo string：网站logo图片路径， 如果不传生成的二维码图片中间没有网站logo。
	 */
	function __construct($data, $fileName, $ecc, $size, $logo = null){
		$this->data = $data;
		$this->fileName = (!$fileName ? false : $fileName);
		$this->ecc = (!$ecc ? 'L' : $ecc);
		$this->size = (!$size ? 4 : $size);
		$this->logo = $logo;
	}


	/**
	 * Create QRCODE 生成二维码的方法
	 * 
	 * @return false ：该文件存在； true：生成二维码成功。
	 */
	public function createQr(){
		if($this->data) {
			if($this->fileName !== false && file_exists($this->fileName)) {
				return false;
			}
			QRcode::png($this->data, $this->fileName, $this->ecc, $this->size);

			if($this->logo != null){
				$QR = $fileName;
				$QR = imagecreatefromstring(file_get_contents($QR));
				$logo = imagecreatefromstring(file_get_contents($this->logo));
				$QR_width = imagesx($QR);
				$QR_height = imagesy($QR);
				$logo_width = imagesx($this->logo);
				$logo_height = imagesy($this->logo);
				$logo_qr_width = $QR_width / 5;
				$scale = $logo_width / $logo_qr_width;
				$logo_qr_height = $logo_height / $scale;
				$from_width = ($QR_width - $logo_qr_width) / 2;
				imagecopyresampled($QR, $this->logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
				imagepng($QR,$this->fileName);
			}
			return true;
		}
	}

}

?>