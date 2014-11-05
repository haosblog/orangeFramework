<?php

//用户中心
function userCenter(&$buffer){
    session_start();
    if($_SESSION['user']['id']){
        $buffer['isLogin'] = 'yes';
        $buffer['user'] = $_SESSION['user'];
        if($buffer['user']['avatar']){
            $buffer['user']['avatar'] =  IMG_DOMAIN.'/member/avatar/'.$buffer['user']['id'].'/'.$buffer['user']['avatar'];
        }
		
		//获取用户未读信息 浏览记录数目等
        $m_member = Helper::loadModel('Member');
        $buffer['num'] = $m_member->getFavAndHisNumBymemID($_SESSION['user']['id']);
    }
}