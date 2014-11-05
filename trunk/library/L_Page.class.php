<?php 

class L_Page { 
     
    static $totalNum;             //总条数
    static $totalPage;             //总页数
    static $pageNow = 1 ;          //当前页
    static $pageNum = 10 ;         //每页显示的数据条数
    static $start   = 0;           //开始查询的条数
    static $varPage = 'page'  ;    //分页变量名
    static $params  = '';          //url传的变量

    /*
     * $count 数据的条数
	 * $listRow 每页显示的条数
     */
    static function show($count = '', $listRow = ''){
        self::$totalNum  = empty($count) ? self::$totalNum : intval($count);
        self::$pageNum   = empty($listRow) ? self::$pageNum : intval($listRow);
        self::$totalPage = ceil(self::$totalNum/self::$pageNum);
        self::$pageNow   = empty($_GET[self::$varPage]) ? 1 : intval($_GET[self::$varPage]);
        
        self::$pageNow = (self::$pageNow < 1) ? 1 : self::$pageNow;
        self::$pageNow = (self::$totalPage <= self::$pageNow) ? self::$totalPage : self::$pageNow;
        self::$start   = self::$pageNum * (self::$pageNow - 1);
        
        if(self::$start < 0) {
        	self::$start = 0;
        }
        
        if(self::$totalNum == 0) return '';
        
        //组成参数
        if(self::$params && is_string(self::$params))
            parse_str(self::$params, $params);
        elseif(is_array(self::$params))
            $params = self::$params;
        elseif(empty(self::$params)){
            $var = !empty($_POST) ? $_POST : $_GET;
            if(empty($var)){
                $params = array();
            }else{
                $params = $var;
            }
        }
        
        $params[self::$varPage] = '__PAGE__';
        $url = '?'.http_build_query($params);

        $prePage  = self::$pageNow - 1;
        $nextPage = self::$pageNow + 1;

        if($prePage > 0){
            $preStr = "<a class='prev' href='".str_replace('__PAGE__', $prePage, $url)."'>上一页</a>&nbsp;"; 
        }else{
            $preStr = '';
        }

        if($nextPage <= self::$totalPage){
            $nextStr = "<a class='next' href='".str_replace('__PAGE__', $nextPage, $url)."'>下一页</a>"; 
        }else{
            $nextStr = '';
        }

        $str = '';
        $indexStr = $preStr;

        //1 2 3 4...
        if(self::$totalPage <= 8){
            for($index = 1; $index <= self::$totalPage; $index++){
                if(self::$pageNow == $index) {
                    $str .= "<span class='current'>{$index}</span>";
                }else {
                    $str .= "<a href='".str_replace('__PAGE__', $index, $url)."'>{$index}</a>&nbsp;";
                }
            }
        }else{
            if(self::$pageNow <= 5){
                for($index = 1; $index <= 8; $index++){
                    if(self::$pageNow == $index) 
                        $str .= "<span class='current'>{$index}</span>";
                    else
                        $str .= "<a href='".str_replace('__PAGE__', $index, $url)."'>{$index}</a>&nbsp;";
                    }
                $str .= " ... <a href='".str_replace('__PAGE__', self::$totalPage, $url)."'>".self::$totalPage."</a>&nbsp;";
            }else{
                $str .= "<a href='".str_replace('__PAGE__', 1, $url)."'>1</a>&nbsp;... ";
                for($index = self::$pageNow-3; $index <= self::$pageNow + 2; $index++){ 
                    if($index >= self::$totalPage){
                        break;
                    }

                    if(self::$pageNow == $index) {
                        $str .= "<span class='current'>{$index}</span>";
                    }else{
                        $str .= "<a href='".str_replace('__PAGE__', $index, $url)."'>{$index}</a>&nbsp;";
                    }
                }
                if($index+1 <= self::$totalPage){
                    $str .= " ... <a href='".str_replace('__PAGE__', self::$totalPage, $url)."'>".self::$totalPage."</a>&nbsp;";
                }else{
                    if(self::$pageNow == self::$totalPage){
                        $str .= "<span class='current'>".self::$totalPage."</span>";
                    }else{
                        $str .= "<a href='".str_replace('__PAGE__', self::$totalPage, $url)."'>".self::$totalPage."</a>&nbsp;";
                    }
                }
            }
        }

        $indexStr .= $str;
        $indexStr .= $nextStr;

        return $indexStr;
    }
    
    /*
	 * $count 数据的条数
	 * $listRow 每页显示的条数
     */
    static function showAjax($count = '', $listRow = ''){
        self::$totalNum  = empty($count) ? self::$totalNum : intval($count);
        self::$pageNum   = empty($listRow) ? self::$pageNum : intval($listRow);
        self::$totalPage = ceil(self::$totalNum/self::$pageNum);
        self::$pageNow   = empty($_GET[self::$varPage]) ? 1 : intval($_GET[self::$varPage]);
        
        self::$pageNow = (self::$pageNow<1) ? 1 : self::$pageNow;
        self::$pageNow = (self::$totalPage <= self::$pageNow) ? self::$totalPage : self::$pageNow;
        self::$start   = self::$pageNum * (self::$pageNow-1);
        
        if(self::$start < 0) {
        	self::$start = 0;
        }
        
        if(self::$totalNum == 0) return '';
        
        //组成参数
        if(self::$params && is_string(self::$params))
            parse_str(self::$params, $params);
        elseif(is_array(self::$params))
            $params = self::$params;
        elseif(empty(self::$params)){
            $var = !empty($_POST) ? $_POST : $_GET;
            if(empty($var)){
                $params = array();
            }else{
                $params = $var;
            }
        }
        
		$tmp = array_keys($params);
        $t    = array_shift($tmp);
        $page = self::$pageNow;
        $type = isset($_GET['type']) ? 'type='.getParam('type') : '';
        $url  = $t.'?isAjax=1&' . $type . '&page=__PAGE__';
        
        $prePage  = self::$pageNow - 1;
        $nextPage = self::$pageNow + 1;
        if($prePage > 0){
            $preStr = "<a class='prev' href='".str_replace('__PAGE__', $prePage, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', $prePage, $url)."\");return false;'>上一页</a>&nbsp;"; 
        }else{
            $preStr = '';
        }

        if($nextPage <= self::$totalPage){
            $nextStr = "<a class='next' href='".str_replace('__PAGE__', $nextPage, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', $nextPage, $url)."\");return false;'>下一页</a>"; 
        }else{
            $nextStr = '';
        }

        $str = '';
        $indexStr = $preStr;

        //1 2 3 4...
        if(self::$totalPage <= 8){
            for($index = 1; $index <= self::$totalPage; $index++){
                if(self::$pageNow == $index) {
                    $str .= "<span class='current'>{$index}</span>";
                }else{
                    $str .= "<a href='".str_replace('__PAGE__', $index, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', $index, $url)."\");return false;'>{$index}</a>&nbsp;";
                }
            }
        }else{
            if(self::$pageNow <= 5){
                for($index = 1; $index <= 8; $index++){
                    if(self::$pageNow == $index) 
                        $str .= "<span class='current'>{$index}</span>";
                    else
                        $str .= "<a href='".str_replace('__PAGE__', $index, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', $index, $url)."\");return false;'>{$index}</a>&nbsp;";
                    }
                $str .= " ... <a href='".str_replace('__PAGE__', self::$totalPage, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', self::$totalPage, $url)."\");return false;'>".self::$totalPage."</a>&nbsp;";
            }else{
                $str .= "<a href='".str_replace('__PAGE__', 1, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', 1, $url)."\");return false;'>1</a>&nbsp;... ";
                for($index = self::$pageNow-3; $index <= self::$pageNow + 2; $index++){ 
                    if($index >= self::$totalPage){
                        break;
                    }
                    if(self::$pageNow == $index) {
                        $str .= "<span class='current'>{$index}</span>";
                    }else{
                        $str .= "<a href='".str_replace('__PAGE__', $index, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', $index, $url)."\");return false;'>{$index}</a>&nbsp;";
                    }
                }
                if($index + 1 <= self::$totalPage){
                    $str .= " ... <a href='".str_replace('__PAGE__', self::$totalPage, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', self::$totalPage, $url)."\");return false;'>".self::$totalPage."</a>&nbsp;";
                }else{
                    if(self::$pageNow == self::$totalPage)
                        $str .= "<span class='current'>".self::$totalPage."</span>";
                    else
                        $str .= "<a href='".str_replace('__PAGE__', self::$totalPage, $url)."' onclick='memberAjaxList(\"".str_replace('__PAGE__', self::$totalPage, $url)."\");return false;'>".self::$totalPage."</a>&nbsp;";
                }
            }
        }
        $indexStr .= $str;
        $indexStr .= $nextStr;

        return $indexStr;
    }
}