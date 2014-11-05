<?php
/**
 * File: Com_weixin.php
 * Functionality:   获取短链接的函数
 * @author hao
 * Date: 2014-4-10 11:56
 *
 */

function getSinaShortUrl($url){
	include BASE_PATH . '/plugin/sdks/sina/config.php' ;
	$url = urlencode($url);
	$requestUrl = 'https://api.weibo.com/2/short_url/shorten.json?source='. WB_AKEY .'&url_long='. $url;

	$returnJson = file_get_contents($requestUrl);
	$shortData = json_decode($returnJson, true);

	if($shortData['urls'][0]['result'] == 1){
		return $shortData['urls'][0]['url_short'];
	} else {
		return false;
	}
}