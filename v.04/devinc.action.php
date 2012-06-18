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

    //将cookie中的变量换成标准的uid, uname
    $cookie['uid']   = $cookie['uniqueid'];
    $cookie['uname'] = $cookie['screen_name'];

    if ($verify === false) {
        return $cookie;
    }

    //需要从weibo平台中获取用户信息
    return s_user_by_uid($uniqueid);
}


//当前请求的时间
function s_action_time() {
    return $_SERVER["REQUEST_TIME"];
}



function s_action_json($data) {
    if ($data === false) {
        //多半是直接函数调用后返回的false
        $data = array(
            'error'     => 500,
            'errmsg'    => '参数错误或服务器调用失败',
        );
    }

    echo json_encode($data);
}


function s_action_xml($data) {
    s_action_json($data);
}



//只提供给flash获取数据
function s_action_data() {
    if (s_bad_get('token', $token)
        || s_bad_string($GLOBALS["HTTP_RAW_POST_DATA"], $data)
    ) {
        return false;
    }
    
    $ret = array();
    $arr = explode($token, $data);

    foreach ($arr as &$item) {
        if (( $pos = strpos($item, '=') ) === false) {
            continue;
        }

        $key = substr($item, 0, $pos);
        $var = substr($item, $pos + 1);

        $ret[$key] = $var;
    }

    return $ret;
}


//返回用户的IPV4地址
function s_action_ip() {
    return "000.000.000.000";
}


//返回http的referer
function s_action_referer() {
    return "";
}

function s_action_error($message="no params.", $code=99, $type="json") {
    $error = array(
        'error'     => $code,
        'errmsg'    => $message,
    );

    //if ($type === "josn") {
        s_action_json($error);

    //} else if ($type === 'xml') {
        //s_action_xml($error);
    //}
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


//重定向
function s_action_page($tpl, $assign) {
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


