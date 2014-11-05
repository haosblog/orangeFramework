<?php
/**
 * File: F_Network.php
 * Functionality: Extra network functions
 * Author: Nic XIE
 * Date: 2012-03-01
 */

/**
 * Get client IP Address
 */
function getClientIP(){
	if (getenv('HTTP_CLIENT_IP')) {
		$clientIP = getenv('HTTP_CLIENT_IP');
	} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
		$clientIP = getenv('HTTP_X_FORWARDED_FOR');
	} elseif (getenv('REMOTE_ADDR')) {
		$clientIP = getenv('REMOTE_ADDR');
	} else {
		$clientIP = $HTTP_SERVER_VARS['REMOTE_ADDR'];
	}

	return $clientIP;
}


/**
 * Is visitor a spider ?
 */
function isSpider(){

	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		return '';
	}

	$searchengine_bot = array(
		'googlebot',
		'mediapartners-google',
		'baiduspider+',
		'msnbot',
		'yodaobot',
		'yahoo! slurp;',
		'yahoo! slurp china;',
		'iaskspider',
		'sogou web spider',
		'sogou push spider'
	);

	$searchengine_name = array(
		'GOOGLE',
		'GOOGLE ADSENSE',
		'BAIDU',
		'MSN',
		'YODAO',
		'YAHOO',
		'Yahoo China',
		'IASK',
		'SOGOU',
		'SOGOU'
	);

	$spider = strtolower($_SERVER['HTTP_USER_AGENT']);

	foreach ($searchengine_bot AS $key => $value) {
		if (strpos($spider, $value) !== false) {
			$spider = $searchengine_name[$key];

			return $spider;
		}
	}

	return '';
}


/**
 *  Get user broswer type
 */
function getUserAgent() {
	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		return '';
	}

	$browser = $browser_ver = '';
	$agent = $_SERVER['HTTP_USER_AGENT'];

	if (preg_match('/MSIE\s([^\s|;]+)/i', $agent, $regs)) {
		$browser = 'Internet Explorer';
		$browser_ver = $regs[1];
	} elseif (preg_match('/FireFox\/([^\s]+)/i', $agent, $regs)) {
		$browser = 'FireFox';
		$browser_ver = $regs[1];
	} elseif (preg_match('/Opera[\s|\/]([^\s]+)/i', $agent, $regs)) {
		$browser = 'Opera';
		$browser_ver = $regs[1];
	} elseif (preg_match('/Netscape([\d]*)\/([^\s]+)/i', $agent, $regs)) {
		$browser = 'Netscape';
		$browser_ver = $regs[2];
	} elseif (preg_match('/safari\/([^\s]+)/i', $agent, $regs)) {
		$browser = 'Safari';
		$browser_ver = $regs[1];
	} elseif (preg_match('/NetCaptor\s([^\s|;]+)/i', $agent, $regs)) {
		$browser = '(Internet Explorer ' . $browser_ver . ') NetCaptor';
		$browser_ver = $regs[1];
	}

	if (!empty($browser)) {
		return addslashes($browser . ' ' . $browser_ver);
	} else {
		return 'Unknow browser';
	}
}


/**
 *  Get user OS
 */
function getUserOS() {
	if (empty($_SERVER['HTTP_USER_AGENT'])) {
		return 'Unknown';
	}

	$os = '';
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

	if (strpos($agent, 'win') !== false) {
		if (strpos($agent, 'nt 5.1') !== false) {
			$os = 'Windows XP';
		} elseif (strpos($agent, 'nt 5.2') !== false) {
			$os = 'Windows 2003';
		} elseif (strpos($agent, 'nt 5.0') !== false) {
			$os = 'Windows 2000';
		} elseif (strpos($agent, 'nt 6.0') !== false) {
			$os = 'Windows Vista';
		} elseif (strpos($agent, 'nt') !== false) {
			$os = 'Windows NT';
		}
	} elseif (strpos($agent, 'linux') !== false) {
		$os = 'Linux';
	} elseif (strpos($agent, 'mac') !== false && strpos($agent, 'pc') !== false) {
		$os = 'Macintosh';
	} else {
		$os = 'Unknown';
	}

	return $os;
}


/**
 *  Submit HTTP request via CURL
 */
function executeHTTPRequest($url, $params, $timeout = 0) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FAILONERROR, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

	/**
	 *  Post ?
	 */
	if (is_array($params) && sizeof($params) > 0) {
		$postBodyString = '';
		foreach ($params as $key => $value) {
			$postBodyString .= "$key=" . urlencode($value) . '&';
		}
		unset($key, $value);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
	}

	$response = curl_exec($ch);

	if (curl_errno($ch)) {
		throw new Exception(curl_error($ch), 0);
	}

	curl_close($ch);
	return $response;
}


/**
 *  邮件发送函数
 *  @param  string  $toMail     接收者邮箱
 *  @param  string  $subject    邮件标题
 *  @param  string  $body       邮件内容
 *  @return string  $message    发送成功或失败消息
 */
function sendMail($toMail, $subject, $body) {
	include BASE_PATH . '/plugin/PHPMailer/class.phpmailer.php';
	include BASE_PATH . '/plugin/PHPMailer/class.smtp.php';
	include CONFIG_PATH. '/Mail_config.php';

	$mail = new PHPMailer();
	if(1 == $Config['mailConfig']['type']){
		$mail->IsSMTP();                                        // 经smtp发送  
		$mail->SMTPAuth = true;                                 // 打开SMTP 认证  
		$mail->Host     = $Config['mailConfig']['server'];      // SMTP 服务器  
		$mail->Port     = $Config['mailConfig']['port'];        // SMTP 端口
		$mail->Username = $Config['mailConfig']['user'];        // 用户名  
		$mail->Password = $Config['mailConfig']['password'];    // 密码  
		$mail->From     = $Config['mailConfig']['from'];        // 发信人  
		$mail->FromName = $Config['mailConfig']['name'];        // 发信人别名  
	}else{
		$mail->IsSendmail();                                    // 系统自带的 SENDMAIL 发送
		$mail->From     = $Config['mailConfig']['sender'];      // 发信人       					    
		$mail->FromName = $Config['mailConfig']['name'];       // 发信人别名 
		$mail->AddAddress($toMail);								//设置发件人的姓名	
	}

	$mail->AddAddress($toMail);                             // 收信人  
	$mail->WordWrap = 50;
	$mail->CharSet = "utf-8";
	$mail->IsHTML(true);                                    // 以html方式发送  
	$mail->Subject = $subject;                              // 邮件标题  
	$mail->Body = $body;                                    // 邮件内空  
	$mail->AltBody = "请使用HTML方式查看邮件。";

	$code = '';
	if (!@$mail->Send()) {
		$code = 0;
	} else {
		$code = 1;
	}
	return $code;
}


/**
 *  短信息发送函数
 *  @param  string $content     发送的内容
 *  @param  string $mobile      手机号码
 *  @return string              成功回复：'num=1&success=18024556469&faile=&err=发送成功！&errid=0'
 */
function sendSms($content, $mobile) {
	Helper::import('SMS');

	$sms = new L_SMS($mobile, $content);
	$result = $sms->send(1);
	
	return $result;
}

/**
 * 邮件发送
 *
 * @param: $name[string]        接收人姓名
 * @param: $email[string]       接收人邮件地址
 * @param: $subject[string]     邮件标题
 * @param: $content[string]     邮件内容
 * @param: $type[int]           0 普通邮件， 1 HTML邮件
 * @param: $notification[bool]  true 要求回执， false 不用回执
 *
 * @return boolean
 */
function send_mail($name, $email, $subject, $content, $type = 0, $notification=false){
	//使用mail函数发送邮件
	$charset = 'utf-8';
	$from = 'admin@513fdw.com';
	$from_name = "房道网";
	if (function_exists('mail')){
		/* 邮件的头部信息 */
		$content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
		$headers = array();
		$headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from . '>';
		$headers[] = $content_type . '; format=flowed';
		if ($notification){
			$headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($from_name) . '?='.'" <' . $from . '>';
		}

		$res = @mail($email, '=?' . $charset . '?B?' . base64_encode($subject) . '?=', $content, implode("\r\n", $headers));

		if (!$res){
			return false;
		}else {
			return true;
		}
	}
}

/**
 * 隐私设置通知
 * @param int $memberID
 * @param string $event
 * @param array $extension 房源是否发布成功等额外信息
 */
function privacy($memberID, $event, $extension = array()){
	if(!$event || !$memberID) {
		return false;
	}

	$fields = array(
		'message',
		'sms',
		'email'
	);
	$where = 'WHERE `memberID` = ' . $memberID;
	$privacy = Helper::loadModel('Privacy')->SelectOne($fields, $where);

	if($privacy){
		if(array_filter($privacy)) {
			foreach($privacy as $k => $v ) {
				$tmp[$k] = json_decode($v, true);
			}

			//查询出该memberID对应的手机和邮箱
			$fields = array('username', 'mobile', 'email');
			$where  = 'WHERE `id` = ' . $memberID.' LIMIT 1';
			$member = Helper::loadModel('Member')->SelectOne($fields, $where);

			switch ($event) {
				case 'm':
					$messageID = 2;
					$subject = '个人资料修改通知';
					$content = '您的个人资料于'.CUR_DATE.'被修改，如果不是您亲自操作，请赶紧修改密码！';
				break;

				case 'f':
					$messageID = 3;
					$subject = '房源收藏通知';
					$content = '您的房源于'.CUR_DATE.'被收藏。';
				break;

				case 'p':
					if($extension['isPublished']) {
						$messageID = 5;
						$subject = '房源审核通知';
						$content = '您发布的房源 "'.$extension['title'].'" 审核通过。';
					} else {
						$messageID = 4;
						$subject = '房源审核通知';
						$content = '您发布的房源 "'.$extension['title'].'" 审核未通过。';
					}
				break;
			}

			//发送站内消息
			if(isset($tmp['message'][$event])) {
				sendMessage($memberID ,$subject, $content ,$memberID);
			}

			//发送短消息
			if(isset($tmp['sms'][$event]) && $member['mobile']) {
				sendSms($content, $member['mobile']);
				$maps = array();
				$maps['mobile'] = $member['mobile'];
				$maps['content'] = $content;
				$maps['status'] = 1;
				Helper::loadModel('SMS')->Insert($maps);
			}

			//发送邮件
			if(isset($tmp['email'][$event]) && $member['email']) {
				send_mail($member['username'], $member['email'], $subject, $content);
			}
		}
	}
}

/**
 * 发送站内消息，比如修改个人资料或房源被收藏时发送提示消息(私有)
 * @param int $memssageID 消息id
 * @param int $memberID   需要发送给的会员id
 * @param int $groupID    群id，默认为0.
 * return int
 */
 function sendMessage($memberID ,$subject ,$content ,$groupID = 0){
	//$maps['messageID'] = $memssageID;
	$maps['memberID']  = $memberID;
	$maps['isRead'] = 0;
	$maps['readTime'] = 0;
	$maps['send_time'] = CUR_TIMESTAMP;
	$maps['title'] = $subject; 
	$maps['message'] = $content;
	$maps['groupID'] = $groupID;

	return Helper::loadModel('Message')->Insert($maps); 
}

/*
 *  根据IP 获取所在城市的信息
 *  @Remark: 需要通过网络查询, DNS 解析等, 可能会很慢, 请谨慎使用 !!!
 */
function getCityBaseOnIP($ip){
	$url = "http://www.youdao.com/smartresult-xml/search.s?type=ip&q=".$ip;
	return file_get_contents($url);
}

//===================================
//
// 功能：IP地址获取真实地址函数
//
//===================================
function convertIP($ip) {
	//IP数据文件路径，请根据情况自行修改
	$dat_path = BASE_PATH.'/asset/QQWry.dat';

	//检查IP地址
	if(!ereg("^([0-9]{1,3}.){3}[0-9]{1,3}$", $ip)){
		return 'IP Address Error';
	}

	//打开IP数据文件
	if(!$fd = @fopen($dat_path, 'rb')){
		return 'IP date file not exists or access denied';
	}

	//分解IP进行运算，得出整形数
	$ip = explode('.', $ip);
	$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
	//获取IP数据索引开始和结束位置
	$DataBegin = fread($fd, 4);
	$DataEnd = fread($fd, 4);
	$ipbegin = implode('', unpack('L', $DataBegin));
	if($ipbegin < 0) $ipbegin += pow(2, 32);
	$ipend = implode('', unpack('L', $DataEnd));
	if($ipend < 0) $ipend += pow(2, 32);
	$ipAllNum = ($ipend - $ipbegin) / 7 + 1;
	$BeginNum = 0;
	$EndNum = $ipAllNum;

	//使用二分查找法从索引记录中搜索匹配的IP记录
	while($ip1num>$ipNum || $ip2num<$ipNum) {
		$Middle= intval(($EndNum + $BeginNum) / 2);
		//偏移指针到索引位置读取4个字节
		fseek($fd, $ipbegin + 7 * $Middle);
		$ipData1 = fread($fd, 4);
		if(strlen($ipData1) < 4) {
			fclose($fd);
			return 'System Error';
		}
		//提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
		$ip1num = implode('', unpack('L', $ipData1));
		if($ip1num < 0) $ip1num += pow(2, 32);
		//提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
		if($ip1num > $ipNum) {
			$EndNum = $Middle;
			continue;
		}
		//取完上一个索引后取下一个索引
		$DataSeek = fread($fd, 3);
		if(strlen($DataSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}
		$DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
		fseek($fd, $DataSeek);
		$ipData2 = fread($fd, 4);
		if(strlen($ipData2) < 4) {
			fclose($fd);
			return 'System Error';
		}

		$ip2num = implode('', unpack('L', $ipData2));
		if($ip2num < 0) $ip2num += pow(2, 32);
		//没找到提示未知
		if($ip2num < $ipNum) {
			if($Middle == $BeginNum) {
				fclose($fd);
				return 'Unknown';
			}
			$BeginNum = $Middle;
		}
	}

	//下面的代码读晕了，没读明白，有兴趣的慢慢读
	$ipFlag = fread($fd, 1);
	if($ipFlag == chr(1)) {
		$ipSeek = fread($fd, 3);
		if(strlen($ipSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}

		$ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
		fseek($fd, $ipSeek);
		$ipFlag = fread($fd, 1);
	}

	if($ipFlag == chr(2)) {
		$AddrSeek = fread($fd, 3);
		if(strlen($AddrSeek) < 3) {
			fclose($fd);
			return 'System Error';
		}
		$ipFlag = fread($fd, 1);
		if($ipFlag == chr(2)) {
			$AddrSeek2 = fread($fd, 3);
			if(strlen($AddrSeek2) < 3) {
				fclose($fd);
				return 'System Error';
			}

			$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
			fseek($fd, $AddrSeek2);
		} else {
			fseek($fd, -1, SEEK_CUR);
		}

		while(($char = fread($fd, 1)) != chr(0))
			$ipAddr2 .= $char;
		$AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
		fseek($fd, $AddrSeek);
		while(($char = fread($fd, 1)) != chr(0))
			$ipAddr1 .= $char;
	} else {
		fseek($fd, -1, SEEK_CUR);
		while(($char = fread($fd, 1)) != chr(0))
			$ipAddr1 .= $char;
		$ipFlag = fread($fd, 1);
		if($ipFlag == chr(2)) {
			$AddrSeek2 = fread($fd, 3);
			if(strlen($AddrSeek2) < 3) {
				fclose($fd);
				return 'System Error';
			}
			$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
			fseek($fd, $AddrSeek2);
		} else {
			fseek($fd, -1, SEEK_CUR);
		}
		while(($char = fread($fd, 1)) != chr(0)){
			$ipAddr2 .= $char;
		}
	}
	fclose($fd);

	//最后做相应的替换操作后返回结果
	if(preg_match('/http/i', $ipAddr2)) {
		$ipAddr2 = '';
	}

	$ipaddr = "$ipAddr1 $ipAddr2";
	$ipaddr = preg_replace('/CZ88.Net/is', '', $ipaddr);
	$ipaddr = preg_replace('/^s*/is', '', $ipaddr);
	$ipaddr = preg_replace('/s*$/is', '', $ipaddr);
	if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
		$ipaddr = 'Unknown';
	}
	return $ipaddr;
}

function getCityBaseOnIPBaidu($ip){
	$url = "http://api.map.baidu.com/location/ip?ak=F454f8a5efe5e577997931cc01de3974&ip=".$ip;
	$jsonData = file_get_contents($url);
	$ipDataArr = json_decode($jsonData, true);
	//返回当前IP对应城市
	return $ipDataArr['content']['address'];
}

function getCityBaseOnLocationBaidu($latitude, $longitude){
	$url = "http://api.map.baidu.com/geocoder?output=json&location=". $latitude .','. $longitude;
	$jsonData = file_get_contents($url);
	$locationArr = json_decode($jsonData, true);
	if($locationArr['status'] == 'OK'){
		return $locationArr['result'];
	} else {
		return false;
	}
}

/**
 * 发送api请求
 * @param type $url		请求路径（action后面的内容）
 * @param type $method	提交方式，GET或POST
 * @param type $postfields	提交参数
 * @param type $formdata	postfids是否可以直接提交
 * @return array			返回获取到的数据（转化为数组）
 */
function sendRequest($url, $postfields = array(), $method = 'get', $cookie = false, $queryArr = array()){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if(strtolower($method) == 'post'){
		curl_setopt($ch, CURLOPT_HEADER, 0 ) ;
		curl_setopt($ch, CURLOPT_POST, 1 );
	}

	if(!empty($postfields)) {
		if(is_array($postfields)){
			foreach($postfields as $key => $value){
				$postdata .= $key .'='. $value .'&';
			}
			$postdata = rtrim($postdata, '&');
		} else {
			$postdata = $postfields;
		}

		curl_setopt($ch,CURLOPT_POSTFIELDS, $postdata);
	}

	curl_setopt($ch, CURLOPT_URL, $url);

	if($cookie){
		$cookie_file = BASE_PATH .'/tmp/'. $cookie .'.txt';	//生成cookie临时文件
		if(!file_exists($cookie_file)){
			fclose(fopen($cookie_file, 'a'));
		}

		curl_setopt($ch,CURLOPT_COOKIEJAR, $cookie_file);	//保存cookie文件
		curl_setopt($ch,CURLOPT_COOKIEFILE, $cookie_file);	//发送cookie文件
	}
	
	if(!empty($queryArr)){
		foreach($queryArr as $key => $val){
			curl_setopt($ch, $key, $val);
		}
	}

	$data = curl_exec($ch);
	curl_close($ch);

	if(isset($data)){
		return $data;
	} else {
		return null;
	}

	return $return;
}