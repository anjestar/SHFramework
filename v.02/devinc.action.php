<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.action.php
//	获取action的相关函数
//
//	s_action_user()
//	    返回服务器请求的时间
//  
//	s_action_time()
//	    返回服务器请求的时间
//  
//	s_action_json($data)
//	    以json返回$data数据
//  
//	s_action_xml($data)
//	    以xml返回$data数据
//
//	s_action_ip()
//	    返回请求的ip地址
//
//
//
////////////////////////////////////////////////////////////////////////////////


function s_action_user($verify=true) {
    //先从memcache中获取
    if (false === ( $sso = new SSOCookie('cookie.conf') )
        || false === ( $cookie = $sso->getCookie() )

        || s_bad_id($cookie['uniqueid'], $uniqueid)
    ) {
        return false;
    }

    if ($verify === false) {
        return $cookie;
    }

    //需要从weibo平台中获取用户信息
    return s_user_by_uid($uniqueid);
}


function s_action_time() {
    return $_SERVER["REQUEST_TIME"];
}


function s_action_json($data) {
    echo json_encode($data);
}


function s_action_xml($data) {
    s_action_json($data);
}


function s_action_ip() {
    return "000.000.000.000";
}


//重定向
function s_action_redirect($url) {
    if (s_bad_string($url)) {
        $url = defined('APP_NAME') ? '/' . APP_NAME : '';
    }

    if (!s_bad_ajax()) {
        return s_action_json(array('error' => 1, 'redirect' => $url));
    }


    //302
    header("Location: {$url}");

    return "";
}


