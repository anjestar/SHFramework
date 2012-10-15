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


function s_action_error($message="no params.", $code=99, $type="json") {
    $error = array(
        'error'     => $code,
        'errmsg'    => $message,
    );

    s_action_json($error);
}


//重定向
function s_action_redirect($url, $delay=0, $msg=false) {

    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("HTTP/1.1 301 Tks Waitting");
    header("Location: {$url}");

    echo "why?";

    exit();
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
    }

    if (strpos($tpl, '/') !== 0) {
        //相对路径
    }


    return s_smarty($tpl, $assign);
}


