<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.action.php
//	获取action的相关函数
//
//	s_action_user($update=true, $checkref=true)
//	    返回服务器请求的时间。$update马上更新，$checkref同源判断
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


function s_action_user($update=true) {
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

    if ($update === false) {
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
            'error'     => 100,
            'errmsg'    => '参数错误',
        );
    }


    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT'); 
    header('Cache-Control: no-cache, must-revalidate'); 
    header('Pragma: no-cache');
    header('content-type: application/json; charset=utf-8');

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


//返回用户的IP地址
function s_action_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        //客户IP地址
        return $_SERVER['HTTP_CLIENT_IP'];

    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //经过代理服务器的IP地址列表
        return $_SERVER['HTTP_X_FORWARDED_FOR'];

    } else if(isset($_SERVER['REMOTE_ADDR'])) {
        //可能是代理服务器的最后一个IP地址
        return $_SERVER['REMOTE_ADDR'];
    } else {
        //没有了，返回默认的IPV4
        return '000.000.000.000';
    }
}


//返回http的referer
function s_action_referer() {
    return $_SERVER['HTTP_REFERER'];
}


//返回http的referer
function s_action_source() {
    return $_SERVER['referer'];
}

//判断是否是JQuery提供的ajax请求
function s_action_is_ajax() {
    @$_SERVER[ 'HTTP_X_REQUESTED_WITH' ] === 'XMLHttpRequest';
}

//显示出错信息，利用/ERROR.tpl（通用错误处理模板）显示用户信息
function s_action_error($message="no params.", $code=99, $url=false) {
    //非ajax状态输出json格式
    $msg = array(
        'url'       => $url ? $url : '/',
        'error'     => $code,
        'errmsg'    => $message,
    );

    if (s_action_is_ajax() === "ajax") {
        return s_action_json($msg);

    } else {
        return s_action_page($msg, '/error.tpl');
    }
}


//重定向
function s_action_redirect($url, $delay=0, $msg=false) {
    if (s_bad_string($url)) {
        $url = defined('APP_NAME') ? '/' . APP_NAME : '';
    }

    if (s_bad_ajax()) {
        if ($delay !== 0) {
            //需要提示，输出页面

            return ;
        }


        //非ajax请求，又没有提示语句，直接302
        if (is_string($msg)) {
            $url .= $msg;
        }

        header("Location: {$url}");

        return ;
    }

    return s_action_json(array('error' => 1, 'redirect' => $url));
}


//返回json格式
function s_action_json($data) {
    if ($data === false) {
        //多半是直接函数调用后返回的false
        $data = array(
            'error'     => 100,
            'errmsg'    => '参数错误',
        );
    }

    echo json_encode($data);
}


//返回tpl文件
function s_action_page($assign=false, $tpl=false) {
    if ($tpl === false) {
        //需要自动设置$tpl路径
        if (s_bad_string($_SERVER['SCRIPT_FILENAME'], $file)
            || false === ( $pos = strrpos($file, '.php') )
            || false === ( $tpl = substr($file, 0, $pos) )
        ) {
            return false;
        }

        //截取php文件，得到tpl文件
        $tpl .= '.tpl';

    } else if (strpos($tpl, '/') === 0) {
        //绝对路径
        $tpl = $_SERVER['DOCUMENT_ROOT'] . $tpl;
    }

    return s_smarty($tpl, $assign);
}
