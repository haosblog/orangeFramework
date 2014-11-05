<?php
/*
 *  File: L_Static.class.php
 *  Functionality: 静态化类
 *  Author: Nic XIE
 *  Date: 2012-09-19
 *  Edited: by Nic XIE 2012-10-26
		1: 为了避免 HTTP REQUEST FAILED, 改由 curl 抓取
 */

Helper::import('Array');
Helper::import('File');

ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");

class L_Static {

    // 成功数
    public $i = 0;
	
    // 失败数
    public $j = 0;
	
    // 成功的URL 
    public $okArray = array();
	
    // 失败的URL
    public $failArray = array();
	
    // 类型: index, second, rent, groupBuy, news, article, comments, footer, menu
    private $type = '';
	
    // 来源数组
    private $sourceArray = array();
	
    // 是否选定而不用直接查数据库
    private $selected = false;
	
    // 来源模板
    private $source = '';
	
    // 目标文件
    private $target = '';

    function __construct($type, $arr = '', $selected = false) {
        $this->type = $type;

        if ($arr) {
            $this->sourceArray = $arr;
        }

        if ($selected) {
            $this->selected = $selected;
        }
    }

    //  创建静态HTML
    public function create() {
        switch ($this->type) {
			// 文章详细
            case 'article':
				$path = HTML_PATH.'/article/';
				if(!file_exists($path)){
					createRDir($path);
				}
				
                if (count($this->sourceArray) > 0) {
                    foreach ($this->sourceArray as $k => $v) {
                        $id  = $v;
						$this->source = '/article/detail?id='.$id;
                        $this->target = $path.$id.STATIC_SUFFIX;

                        $this->build();
                    }
                }
            break;
			
			// 帮助中心文章
            case 'help':
				$path = HTML_PATH.'/help/';
				if(!file_exists($path)){
					createRDir($path);
				}
				
                if (count($this->sourceArray) > 0) {
                    foreach ($this->sourceArray as $k => $v) {
                        $id  = $v;
						$this->source = '/help/detail?id='.$id;
                        $this->target = $path.$id.STATIC_SUFFIX;

                        $this->build();
                    }
                } 
            break;
            //新手指南
            case 'guide':
                $path = HTML_PATH.'/guide/';
                if(!file_exists($path)){
                    createRDir($path);
                }
                
                if (count($this->sourceArray) > 0) {
                    foreach ($this->sourceArray as $k => $v) {
                        $id  = $v;
                        $this->source = '/weixin/guide/guideDetail?id='.$id;
                        $this->target = $path.$id.STATIC_SUFFIX;

                        $this->build();
                    }
                } 
            break;
        }
    }

    // 生成
    private function build() {
		$content = $this->getContents(SERVER_DOMAIN.$this->source);
		
        $code = file_put_contents($this->target, $content);
		
        if ($code) {
            $this->i++;
            $this->okArray[] = $this->source;
        } else {
            $this->j++;
            $this->failArray[] = $this->source;
        }
    }
	
	
	private function getContents($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)');
		$content = curl_exec($curl);
		
		curl_close($curl);
		return $content;
    }

}