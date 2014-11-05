<?php
/**
 * File: F_Basic.php
 * Functionality: Global basic functions 
 * Author: Nic XIE & IT Technology Department
 * Date: 2011-11-20
 */

// Anti_SQL Injection, escape quotes
function filter($content) {
    if (!get_magic_quotes_gpc()) {
        return addslashes($content);
    } else {
        return $content;
    }
}

// Get parameter value via GET or POST
// Remove xss if $xss is true
function getParam($paramName, $xss = true) {
    $arr = isset($_GET[$paramName]) ? $_GET[$paramName] : $_POST[$paramName];

    //return is_array($arr) ? array_map(__FUNCTION__, $arr) : filter(stripSQLChars(stripHTML(trim($arr))));

    if (!isset($arr)) {
        return null;
    }

    if (is_array($arr)) {
		safeField($arr, $xss);
    } else {
        $arr = filter(stripSQLChars(stripHTML(trim($arr), $xss)));
    }

    return $arr;
}

function safeField($data, $xss){
	$return = array();
	foreach($data as $key => $value){
		if(is_array($value)){
			$return[$key] = safeField($value, $xss);
		} else {
			$return[$key] = filter(stripSQLChars(stripHTML(trim($value), $xss)));
		}
	}

	return $return;
}

//对字符串等进行过滤
function filterStr($arr) {  
	if (!isset($arr)) {
        return null;
    }

    if (is_array($arr)) {
        foreach ($arr as $k => $v) {
            $arr[$k] = filter(stripSQLChars(stripHTML(trim($v), true)));
        }
    } else {
        $arr = filter(stripSQLChars(stripHTML(trim($arr), true)));
    }

    return $arr;
}

// Redirect directly
function redirect($URL = '', $second = 0) {
    if (!isset($URL)) {
        $URL = $_SERVER['HTTP_REFERER'];
    }
        ob_start();
        ob_end_clean();
        header("Location: ".$URL, TRUE, 302); //header("refresh:$second; url=$URL", TRUE, 302);
        ob_flush(); //可省略
        exit;
}


// Redirect and display message
function gotoURL($message = '', $URL = '') {
    if (!isset($URL)) {
        $URL = $_SERVER['HTTP_REFERER'];
    }

    if (isset($message)) {
        jsAlert($message);
    }

    echo "<script type='text/javascript'>window.location.href='$URL'</script>";
}

/*
 *Functionality: Generate Single-language[Chinese-simplified] pagenation navigator
  @Params:
  Int $page: current page
  Int $totalPages: total pages
  String $URL: target URL for pagenation
  Int $count: total records
  String $query: query string for SEARCH
 *  @Return: String pagenation navigator link
 */

function generatePageLink($page, $totalPages, $URL, $counts, $query = '') {
	$URL .= (strpos($URL, '?') === false ? '?' : '&');
    // First:
    $first = '首 页';
    $first = "<a href=".$URL."page=1$query>$first</a>";

    // Prev:
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    $prev = "<a href=".$URL."page=$previousPage$query>$prev</a>";

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    $next = "<a href=".$URL."page=$nextPage$query>$next</a>";

    // Last
    $last = '末 页';
    $last = "<a href=".$URL."page=$totalPages$query>$last</a>";

    // Total:
    $total = '共';

    $pageLink = $total . ' ' . $counts . '&nbsp;&nbsp;' . $first . '&nbsp;&nbsp;' . $prev;
    $pageLink .= '&nbsp;&nbsp;' . $next . '&nbsp;&nbsp;' . $last . '&nbsp;&nbsp;' . $page . '/' . $totalPages . '&nbsp';

    return $pageLink;
}

// Functionality: 生成带"转至"第几页的分页导航栏
function generatePageLink2($page, $totalPages, $URL, $counts, $query = '') {
	
	$sign = '?';
	if(strpos($URL, '?') !== FALSE){
		$sign = '&';
	}

    // First:
    $first = '首 页';
    $first = '<a href='.$URL.$sign.'page=1'.$query.'>'.$first.'</a>';

    // Prev:
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    $prev = '<a href='.$URL.$sign.'page='.$previousPage.$query.'>'.$prev.'</a>';

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    $next = '<a href='.$URL.$sign.'page='.$nextPage.$query.'>'.$next.'</a>';

    // Last
    $last = '末 页';
    $last = '<a href='.$URL.$sign.'page='.$totalPages.$query.'>'.$last.'</a>';

    // Total:
    $total = '共';

    $pageLink = $total . ' ' . $counts . '&nbsp;&nbsp;' . $first . '&nbsp;&nbsp;' . $prev;
    $pageLink .= '&nbsp;&nbsp;' . $next . '&nbsp;&nbsp;' . $last . '&nbsp;&nbsp;';

    $pageLink .= '<input type="text" id="txtGoto" name="txtGoto" size="5" maxlength="5" />';
    $pageLink .= '&nbsp;<input type ="button" class="btn btn-primary" id="btnGoto" name="btnGoto" value="转至" />';

    $pageLink .= '&nbsp;<span id="currentPage">' . $page . '</span>/<span id="totalPages">' . $totalPages . '</span>&nbsp';

    $pageLink .= '<br /><input type="hidden" id="self_url" name="self_url" value="' . $URL . '">';

    return $pageLink;
}


// 经纪人, 置业专家, 金牌门店使用的分页链接
function generateStorePageLink($page, $totalPages, $url, $total) {
	$prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    if($page == 1){
    	$prev = "<li id='b_prev1'><a href='javascript:void(0)'><span><img alt='上一页' src='" . IMG_PATH . "/btn/b_prev1.png'>".$prev."</span></a></li>";
    } else {
    	$prev = "<li id='b_prev'><a href='" . $url . $previousPage ."' onclick='javascript:prev();return false;'><span><img alt='上一页' src='" . IMG_PATH . "/btn/b_prev.png'>".$prev."</span></a></li>";
    }
    
	// 加上页数 [取当前页的前五与后五]
	// 1: 总页数 <= 5 就输出全部
	$pagesURL = '';
	if($totalPages <=11){
		for($i=1; $i<=$totalPages; $i++){
			if($i == $page){
				$pagesURL .= "<li id='b_checked'><a href='javascript:void(0)'>";
				$pagesURL .= "<span>$i</span>";
			}else{
				$pagesURL .= "<li><a href='" . $url . $i ."' onclick='javascript:gotoPage(\"" .$i."\");return false;'>";
				$pagesURL .="<span>$i</span>";
			}
			$pagesURL .= "</a></li>";
		}
	}else{
		if($page <= 5){
			for($i=1; $i<=11; $i++){
				if($i == $page){
					$pagesURL .= "<li id='b_checked'><a href='javascript:void(0)'>";
					$pagesURL .="<span>$i</span>";
				}else{
					$pagesURL .= "<li><a href='" . $url . $i ."' onclick='javascript:gotoPage(\"" .$i."\");return false;'>";
					$pagesURL .="<span>$i</span>";
				}
				$pagesURL .= "</a></li>";
			}
		}else{
			if($totalPages < $page + 5){
				for($i=$totalPages-10; $i<=$totalPages; $i++){
					if($i == $page){
						$pagesURL .= "<li id='b_checked'><a href='javascript:void(0)'>";
						$pagesURL .="<span>$i</span>";
					}else{
						$pagesURL .= "<li><a href='" . $url . $i ."' onclick='javascript:gotoPage(\"" .$i."\");return false;'>";
						$pagesURL .="<span>$i</span>";
					}
					$pagesURL .= "</a></li>";
				}
			}else{
				for($i=$page-5; $i<=$page; $i++){
					if($i == $page){
						$pagesURL .= "<li id='b_checked'><a href='javascript:void(0)'>";
						$pagesURL .="<span>$i</span>";
					}else{
						$pagesURL .= "<li><a href='" . $url . $i ."' onclick='javascript:gotoPage(\"" .$i."\");return false;'>";
						$pagesURL .="<span>$i</span>";
					}
					$pagesURL .= "</a></li>";
				}
				
				$max = ($page+5) > $totalPages ? $totalPages : ($page+5);
				for($i=$page+1; $i<=$max; $i++){
					if($i == $page){
						$pagesURL .= "<li id='b_checked'><a href='javascript:void(0)'>";
						$pagesURL .="<span>$i</span>";
					}else{
						$pagesURL .= "<li><a href='" . $url . $i ."' onclick='javascript:gotoPage(\"" .$i."\");return false;'>";
						$pagesURL .="<span>$i</span>";
					}
					$pagesURL .="</a></li>";
				}
			}
		}
	}
	
	$prev .= $pagesURL;

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    if($page == $totalPages){
    	$next = "<li id='b_next1'><a href='javascript:void(0)'><span>" . $next . "<img alt='" .  $next ."' src='" . IMG_PATH  . "/btn/b_next1.png'></span></a></li>";
    } else {
    	$next = "<li id='b_next'><a href='" . $url . $nextPage ."' onclick='javascript:next();return false;'><span>" . $next . "<img alt='" .  $next ."' src='" . IMG_PATH  . "/btn/b_next.png'></span></a></li>";
    }

    $pageLink =$first . $prev;
    $pageLink .= $next . $last;

    return $pageLink ;
}


/**
 * 采集的分页
 */
function generateCollectionLink($page, $totalPages, $URL, $counts, $query = '') {
	if($counts == 0){
		return '';
	}
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    if($page == 1){
    	$prev = " <a href='javascript:void(0)' class='a1' >" . $prev . "</a> ";
    } else {
    	$prev = " <a href='" . $URL . $previousPage ."' class='a1' >" . $prev . "</a> ";
    }
    
	// 加上页数 [取当前页的前五与后五]
	// 1: 总页数 <= 5 就输出全部
    
	$pagesURL = '';
	if($totalPages <=5){
		for($i=1; $i<=$totalPages; $i++){
			if($i == $page){
				$pagesURL .= " <span>" . $i . " </span> ";
			}else{
				$pagesURL .= " <a href='" . $URL . $i ."'>" . $i . "</a> " ;
			}
		}
	}else{
		if($page <= 5){
			for($i=1; $i<=$page+5; $i++){
				if($i == $page){
					$pagesURL .= " <span>" . $i . " </span> ";
				}else{
					$pagesURL .= " <a href='" . $URL . $i ."'>" . $i . "</a> " ;
				}
			}
		}else{
			for($i=$page-5; $i<=$page; $i++){
				if($i == $page){
					$pagesURL .= " <span>" . $i . " </span> ";
				}else{
					$pagesURL .= " <a href='" . $URL . $i ."'>" . $i . "</a> " ;
				}
			}
			
			$max = ($page+5) > $totalPages ? $totalPages : ($page+5);
			for($i=$page+1; $i<=$max; $i++){
				if($i == $page){
					$pagesURL .= " <span>" . $i . " </span> ";
				}else{
					$pagesURL .= " <a href='" . $URL . $i ."'>" . $i . "</a> " ;
				}
			}
		}
	}
	
	$prev .= $pagesURL;

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    if($page == $totalPages){
    	$next = " <a href='javascript:void(0)' class='a1' >" . $next . "</a> ";
    } else {
    	$next = " <a href='" . $URL . $nextPage ."' class='a1' >" . $next . "</a> ";
    }
    
    $total = ' <a class="a1">' . $counts . '条</a> ';

    
    $pageLink = $total . $first . $prev;
    $pageLink .= $next . $last ;

    return $pageLink ; 
}


/**
 * 经纪人的列表分页
 */ 
function generateAgentLink($page, $totalPages, $URL, $counts, $URLA = '' ,$saleType = '') {
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    if($page == 1){
    	$prev = "<span class='prev' title='上一页'></span>";
    } else {
    	$prev = "<a class='prev' href='" . $URLA . $previousPage ."/' onclick='javascript:clientAjaxList(\"" . $URL . $previousPage . "\");return false;'>上一页</a>";
    }
    
	// 加上页数 [取当前页的前五与后五]
	// 1: 总页数 <= 5 就输出全部
    
	$pagesURL = '';
	if($totalPages <=14){
		$pagesURL .= forAgent($page,1,$totalPages,$URL,$URLA);
	}else{
		if($page <= 5){
			
			$pagesURL .= forAgent($page,1,5,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			$cenNum = ceil($totalPages / 2);
			$pagesURL .= forAgent($page,$cenNum,$cenNum+2,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forAgent($page,$totalPages-2,$totalPages,$URL,$URLA);
			
		}elseif($page > $totalPages - 5){
			$pagesURL .= forAgent($page,1,3,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			$cenNum = ceil($totalPages / 2);
			
			$pagesURL .= forAgent($page,$cenNum,$cenNum+2,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forAgent($page,$totalPages-4,$totalPages,$URL,$URLA);
		}else{
			$pagesURL .= forAgent($page,1,3,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			if($page == 6){
				$startI = $page - 1;
				$endI   = $page + 3;
			}elseif($page == $totalPages - 5){
				$startI = $page - 3;
				$endI = $page + 1;
			}else{
				$startI = $page - 2;
				$endI = $page + 2;
			}
			$pagesURL .= forAgent($page,$startI,$endI,$URL,$URLA);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forAgent($page,$totalPages-2,$totalPages,$URL,$URLA);
		}
	}
	
	$prev .= $pagesURL;

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    if($page == $totalPages){
    	$next = "<span class='next' title='下一页'></span>";
    } else {
    	$next = "<a class='next' href='" . $URLA . $nextPage ."/' onclick='javascript:clientAjaxList(\"" . $URL . $nextPage ."\");return false;'>下一页</a>";
    }
    
    $total = '';

    $pageLink = $prev;
    $pageLink .= $next  ;

    return $pageLink ;
}

/**
 * 经纪人的列表分页中的for 循环公用函数
 */
function forAgent($page, $startNum, $endNum, $URL, $URLA){
	for($i=$startNum; $i<=$endNum; $i++){
		if($i == $page){
			$pagesURL .= "<span class='current'>";
			$pagesURL .= "$i";
			$pagesURL .= "</span>";
		}else{
			$pagesURL .= "<a href='" . $URLA . $i ."/' onclick='javascript:clientAjaxList(\"" . $URL . $i ."\");return false;'>";
			$pagesURL .="$i";
			$pagesURL .= "</a>";
		}
	}
	return $pagesURL;
}


/**
 *  Functionality: 生成供静态化 URL 用并且带有 GOTO 功能的分页导航
 *  Remark: 首页, 上一页, 下一页, 末页中的 href 为 javascript:;
 *          而是赋予了class, 当前页与总页则使用了span, 模板中 JQuery 点击事件触发
 *          $('.pg_index').click(function(){ ... });
 */
function staticPageLink($page, $totalPages, $URL, $counts, $query = '') {

    // First:
    $first = '首 页';
    $first = "<a class='pg_index pointer'>$first</a>";

    // Prev:
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    $prev = "<a class='pg_prev pointer' >$prev</a>";

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    $next = "<a class='pg_next pointer'>$next</a>";

    // Last
    $last = '末 页';
    $last = "<a class='pg_last pointer'>$last</a>";

    // Total:
    $total = '共';

    $pageLink = $total . ' ' . $counts . '&nbsp;&nbsp;' . $first . '&nbsp;&nbsp;' . $prev;
    $pageLink .= '&nbsp;&nbsp;' . $next . '&nbsp;&nbsp;' . $last . '&nbsp;&nbsp;';

    $pageLink .= '<input type="text" id="txtGoto" name="txtGoto" size="3" maxlength="3" />';
    $pageLink .= '&nbsp;<input type ="button" id="btnGoto" name="btnGoto" value="转至" />';

    $pageLink .= '&nbsp;<span id="currentPage">' . $page . '</span>/<span id="totalPages">' . $totalPages . '</span>&nbsp';

    $pageLink .= '<br /><input type="hidden" id="self_url" name="self_url" value="' . $URL . '">';

    return $pageLink;
}


/**
 *  订单的列表分页
 */ 
function generateOrderLink($page, $totalPages, $URL, $counts, $qs = '') {
    $prev = '上一页';
    $previousPage = ($page > 1) ? $page - 1 : 1;
    if($page == 1){
    	$prev = "<a href='javascript:;' class='prev' title='上一页'></a>";
    } else {
    	$prev = "<a class='prev' href='" .$URL.'?page='.$previousPage.$qs."'>上一页</a>";
    }
    
	// 加上页数 [取当前页的前五与后五]
	// 1: 总页数 <= 5 就输出全部
	$pagesURL = '';
	if($totalPages <= 14){
		$pagesURL .= forOrder($page, 1, $totalPages, $URL, $qs);
	}else{
		if($page <= 5){
			$pagesURL .= forOrder($page, 1, 5, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			$cenNum = ceil($totalPages / 2);
			$pagesURL .= forOrder($page, $cenNum, $cenNum+2, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forOrder($page, $totalPages - 2, $totalPages, $URL, $qs);
		}elseif($page > $totalPages - 5){
			$pagesURL .= forOrder($page, 1, 3, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			$cenNum = ceil($totalPages / 2);
			
			$pagesURL .= forOrder($page, $cenNum, $cenNum + 2, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forOrder($page, $totalPages - 4, $totalPages, $URL, $qs);
		}else{
			$pagesURL .= forOrder($page, 1, 3, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			if($page == 6){
				$startI = $page - 1;
				$endI   = $page + 3;
			}elseif($page == $totalPages - 5){
				$startI = $page - 3;
				$endI = $page + 1;
			}else{
				$startI = $page - 2;
				$endI = $page + 2;
			}
			$pagesURL .= forOrder($page, $startI, $endI, $URL, $qs);
			
			$pagesURL .="<span>...</span>";
			$pagesURL .= forOrder($page, $totalPages - 2, $totalPages, $URL, $qs);
		}
	}
	
	$prev .= $pagesURL;

    // Next:
    $next = '下一页';
    $nextPage = ($page == $totalPages) ? $totalPages : $page + 1;
    if($page == $totalPages){
    	$next = "<a href='javascript:;' class='next' title='下一页'></a>";
    } else {
    	$next = "<a class='next' href='" . $URL.'?page='.$nextPage.$qs."'>下一页</a>";
    }

	$link .= $prev;
	$link .= $next;
    return $link;
}

/**
 * 订单的列表分页中的for 循环公用函数
 */
function forOrder($page, $startNum, $endNum, $URL, $qs = ''){
	for($i = $startNum; $i <= $endNum; $i++){
		if($i == $page){
			$pagesURL .= "<span class='current'>";
			$pagesURL .= "$i";
			$pagesURL .= "</span>";
		}else{
			$pagesURL .= "<a href='" .$URL.'?page='.$i.$qs."'>";
			$pagesURL .="$i";
			$pagesURL .= "</a>";
		}
	}
	return $pagesURL;
}

// Get current microtime
function calculateTime() {
    list($usec, $sec) = explode(' ', microtime());
    return ((float) $usec + (float) $sec);
}


/**
 *  Strip specail SQL chars
 */
function stripSQLChars($str) {
    $replace = array('SELECT', 'INSERT', 'DELETE', 'UPDATE', 'CREATE', 'DROP', 'VERSION', 'DATABASES',
        'TRUNCATE', 'HEX', 'UNHEX', 'CAST', 'DECLARE', 'EXEC', 'SHOW', 'CONCAT', 'TABLES', 'CHAR', 'FILE',
        'SCHEMA', 'DESCRIBE', 'UNION', 'JOIN', 'ALTER', 'RENAME', 'LOAD', 'FROM', 'SOURCE', 'INTO', 'LIKE', '*','PING', 'PASSWD');
	
    return str_ireplace($replace, '', $str);
}


/**
 * 裁剪中文
 * 
 * @param type $string
 * @param type $length
 * @param type $dot
 * @return type
 */
function cutstr($string, $length, $dot = ' ...') {
	if(strlen($string) <= $length) {
		return $string;
	}

	$pre = chr(1);
	$end = chr(1);
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);

	$strcut = '';
	if(strtolower(CHARSET) == 'utf-8') {

		$n = $tn = $noc = 0;
		while($n < strlen($string)) {

			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}

			if($noc >= $length) {
				break;
			}

		}
		if($noc > $length) {
			$n -= $tn;
		}

		$strcut = substr($string, 0, $n);

	} else {
		$_length = $length - 1;
		for($i = 0; $i < $length; $i++) {
			if(ord($string[$i]) <= 127) {
				$strcut .= $string[$i];
			} else if($i < $_length) {
				$strcut .= $string[$i].$string[++$i];
			}
		}
	}

	$strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	$pos = strrpos($strcut, chr(1));
	if($pos !== false) {
		$strcut = substr($strcut,0,$pos);
	}
	return $strcut.$dot;
}


function pr($arr) {
	echo '<pre>';
    print_r($arr);
	echo '</pre>';
}


function pp() {
	echo '<pre>';
    print_r($_POST);
	echo '</pre>';
}


/**
 *  JavaScript alert
 */
function jsAlert($msg) {
    echo "<script type='text/javascript'>alert(\"$msg\")</script>";
}


/**
 *  JavaScript redirect
 */
function jsRedirect($url, $die = true) {
    echo "<script type='text/javascript'>window.location.href=\"$url\"</script>";
    if($die){
    	die;
    }
}


function stripHTML($content, $xss = true) {
    $search = array("@<script(.*?)</script>@is",
        "@<iframe(.*?)</iframe>@is",
        "@<style(.*?)</style>@is",
		"@<(.*?)>@is"
    );

    $content = preg_replace($search, '', $content);

	if($xss){
		$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 
		'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 
		'layer', 'bgsound', 'title', 'base');
								
		$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 		'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
		$ra = array_merge($ra1, $ra2);
		
		$content = str_ireplace($ra, '', $content);
	}

    return strip_tags($content);
}

function removeXSS($val) {
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <javaΘscript>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

        // &#x0040 @ search for the hex values
        $val = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
        // @ @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
    }

	// now the only remaining whitespace attacks are \t, \n, and \r
	$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 
							'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 
							'layer', 'bgsound', 'title', 'base');
							
	$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 		'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
					$pattern .= '|(&#0{0,8}([9][10][13]);?)?';
					$pattern .= ')?';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}

	return $val;
}


function filterURLparam($urlParamString, $paramName) {
    $arr = array();
    foreach (explode("&", $urlParamString) as $val) {
        if (stripos($val, $paramName) === false) {
            $arr[] = $val;
        } else {
            continue;
        }
    }
    return implode("&", $arr);
}


// 去掉市，县，区，乡，镇，村
function stripAreaKeyWord($str){
	include CONFIG_PATH.'/AreaExcepction_config.php';
	
	// 去除特例
	if(in_array($str, $Config['AreaExcepction'] )){
		$result = $str;
	}else{
		$search = array('市', '县', '区', '镇', '乡', '村');
		$result =  str_replace($search, '', $str);
	}
	
	return $result;
}

// verify page
function verifyPage($page, $totalPages){
	if ($page > $totalPages || !is_numeric($page) || $page <= 0) {
		$page = 1;
	}
	
	return $page;
}

/**
 * 根据page和pageCount计算limit的值
 * 
 * @param type $page		页码
 * @param type $pageCount	一页内的条数
 * @return array($start, $count)
 */
function getLimit($page, $pageCount){
	$start = ($page - 1) * $pageCount;
	return array($start, $pageCount);
}


/**
 * 编号生成
 * @param int $bid 业务id
 *      1 => 房源编号 
 * @return string $string 生成后的编号
 */
function generateNO($bid) {
    $prefix = '';
    switch ($bid) {
        case 1:
            $prefix = 'HO';
        break;
		
        case 2:
        	$prefix = 'GR';
        break;
        
        case 3:
        	$prefix = 'CO';
        break;
        
        case 4:
        	$prefix = 'OR';
        break;
        
    }
    return $prefix . strrev(time()) . rand(100, 999);
}


/**
 * Echo and die
 */
function eand($msg){
	echo $msg; die;
}


/**
 * Echo html br
 */
function br(){
	echo '<br />';
}


/**
 * Echo html hr
 */
function hr(){
	echo '<hr/>';
}


// echo hidden div with msg
function echoHiddenDiv($msg){
	$html = '<div style="display:none">'.$msg.'</div>';
	echo $html;
}

/**
 * 得到来源页面
 * $needle 要替换成的url
 * $replace 需要被替换的url
 * 
 * 实例：getSource('/member', array('/member/register','/member/logout'))
 * 将来源网址中的register logout 操作换成index操作
 */
function getSource($needle = null, $replace = null){
	if($_SERVER['HTTP_REFERER']) {
		$url = $_SERVER['HTTP_REFERER'];
	} else {
		$url = '/index/index';
	}

	if(is_string($replace)) {
		$arr = array($replace);
	} else if(is_array($replace)) {
		$arr = $replace;
	}
	
	if($arr) {
		foreach($arr as $v) {
			if(strpos($url, $v) !== false) {
				$url = $needle;
			}
		}
	}

	if($url == '/index/index') {
		$url = '/';
	}
	return $url;
}


/**
  * Functionality: 根据两个银行的信息计算出手续费
  * @param string $destination => 汇入银行缩写, 如 CCB
  * @param string $destinationLocation => 汇入银行所在地行政代号, 如 440100
  * @param int $fee => 转出的金额
  * @return int 最终的手续费
  */
function calcFee($destination, $destinationLocation, $fee) {
	include CONFIG_PATH.'/BankFee_config.php';
	$original  = $Config['BankFee']['basic']['bank'];
	$orginalLocation = $Config['BankFee']['basic']['location'];
	
	// 同行本城
	$bank = $location = 1;
	if($original != $destination) {
		$bank = 2;
	}
	
	if($orginalLocation != $destinationLocation) {
		$location = 2;
	}
	
	$type = 0;
	
	// T1 本行同城
	if ($bank == 1 && $location == 1){
		$type = 'T1';
	}
	
	// T2 本行异地
	if ($bank == 1 && $location == 2){
		$type = 'T2';  
	}
	
	// T3 跨行同城
	if ($bank == 2 && $location == 1){
		$type = 'T3';  
	}
	
	// T4 跨行异地
	if ($bank == 2 && $location == 2){
		$type = 'T4';  
	}
	
	$t = $Config['BankFee'][$type];
	
	$money = round(($fee * $t['percent']) / 100);
	
	$final = array();
	$final['remark'] = $t['remark'];
	if ($money <= $t['min']){
		$final['fee'] = $t['min'];
	}else if ($money >=  $t['max']){
		$final['fee'] =$t['max'];
	}else{
		$final['fee'] = $money;
	}
	
	return $final;
}

/**
  * Functionality: 计算个人所得税
  * @param string $income => 实际收入
  * @param string $basic => 个税起征点
  * @return int 最终的个人所得税
  */
function personalTax($income, $basic=3500){
	$fee = $income - $basic;
	$p = $tax = 0;
	if( $fee >0 && $fee <= 1500 ) {
		$p = 3;
		$tax = 0;
	} elseif ( $fee > 1500 && $fee <= 4500 ) {
		$p = 10;
		$tax = 105;
	} elseif ( $fee > 4500 && $fee <= 9000 ) {
		$p = 20;
		$tax = 555;
	} elseif ( $fee > 9000 && $fee <= 35000 ) {
		$p = 25;
		$tax = 1005;
	} elseif ( $fee > 35000 && $fee <= 55000 ) {
		$p = 30;
		$tax = 2755;
	} elseif ( $fee > 55000 && $fee <= 80000 ) {
		$p = 35;
		$tax = 5505;
	} elseif ( $fee > 80000 ) {
		$p = 45;
		$tax = 13505;
	}
	
	return ($fee * $p / 100 - $tax);
}

/**
  * Functionality: 营业税
  * @param string $fee => 实际收入
  * @return int 最终的营业税
  */
function businessTax($fee){
	$p = 0;
	if( $fee > 0 && $fee <= 15000 ) {
		$p = 5;
	} elseif ( $fee > 15000 && $fee <= 30000 ) {
		$p = 10;
	} elseif ( $fee > 30000 && $fee <= 60000 ) {
		$p = 20;
	} elseif ( $fee > 60000 && $fee <= 100000 ) {
		$p = 30;
	} elseif ( $fee > 100000 ) {
		$p = 35;
	}
	
	return ($fee * $p / 100);
}


/**
 *
 * Description 友好显示时间
 * @param int $time 要格式化的时间戳 默认为当前时间
 * @return string $text 格式化后的时间戳
 * @author yijianqing
 */
function mdate($time = NULL) {
	$text = '';
	$time = $time === NULL || $time > time() ? time() : intval($time);
	$t = time() - $time; //时间差 （秒）
	if ($t == 0)
		$text = '刚刚';
	elseif ($t < 60)
		$text = $t . '秒前'; // 一分钟内
	elseif ($t < 60 * 60)
		$text = floor($t / 60) . '分钟前'; //一小时内
	elseif ($t < 60 * 60 * 24)
		$text = floor($t / (60 * 60)) . '小时前'; // 一天内
	elseif ($t < 60 * 60 * 24 * 3)
		$text = floor($time/(60*60*24)) ==1 ?'昨天 ' . date('H:i', $time) : '前天 ' . date('H:i', $time) ; //昨天和前天
	elseif ($t < 60 * 60 * 24 * 30)
		$text = date('m月d日 H:i', $time); //一个月内
	elseif ($t < 60 * 60 * 24 * 365)
		$text = date('m月d日', $time); //一年内
	else
		$text = date('Y年m月d日', $time); //一年以前
	return $text;
}

/**
  *  格式化 wp 的文章内容
  *  1: 文章前加 <p>
  *  2: 换行符替换为 </p><p>
  *  3: 文章最后加 </p>
  */
function formatWPContent($content){
	$result = '<p>';
	$result .= preg_replace('/\n+/', '</p><p>', $content);
	$result .= '</p>';
	return $result;
}

// Highlight keyword
function highlight($str, $find, $color){
	return str_replace($find, '<font color="'.$color.'">'.$find.'</font>', $str);
}


// 格式化新盘的户型
function room_format($room){
	$room = str_replace(',', '&nbsp;&nbsp;', $room);
	$search  = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
	$replace = array('一室', '二室', '三室', '四室', '五室', '六室', '七室', '八室', '九室');
	return str_replace($search, $replace, $room);
}

/**
 * [datecmp 按天数与当前时间进行比较]
 * @param  {[type]} cmpTime [被比较时间戳s(这里应该大于当前时间)]
 * @return {[type]}         [description]
 */
function datecmp($cmpTime){
    //按天比较相差几天
    $cmpdate = ceil(($cmpTime - CUR_TIMESTAMP) / (3600*24));
    return $cmpdate;
}

/**
 * 获取文件缓存的数据
 * 缓存规则：
 * 将filename使用16位MD5加密，生成一个唯一字符串，并将该字符串作为函数名
 * 需要缓存的数据将被var_export为PHP代码并由该函数return
 * getCache时调用该函数得到数据
 * 
 * @param type $filename	缓存文件名，不包括完整路径
 */
function getCache($filename){
	$filename = RUNTIME_PATH . $filename;
	$function = 'cache_'. substr(md5($filename),8,16);
	if(!file_exists($filename)){//文件不存在
		return false;
	}
	
	require $filename;
	
	if(!function_exists($function)){
		return false;
	}
	
	return $function();
}

/**
 * 将数据写入文件缓存
 * 缓存规则：
 * 将filename使用16位MD5加密，生成一个唯一字符串，并将该字符串作为函数名
 * 需要缓存的数据将被var_export为PHP代码并由该函数return
 * 
 * @param type $filename	缓存文件名，不包括完整路径
 * @param type $data		要缓存的数据
 */
function saveCache($filename, $data = array()){
	$filename = RUNTIME_PATH . $filename;
	$function = 'cache_'. substr(md5($filename),8,16);
	
	$code = var_export($data, true);
	//生成缓存文件的PHP代码
	$fileText = '<?php'. NL .
		'function '. $function .'(){'. NL .
		'return '. $code .';' . NL .
		'}';
			
	file_put_contents($filename, $fileText);
}