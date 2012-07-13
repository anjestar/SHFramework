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


function s_action_user($update=true, $checkref=true) {
    //检查referer
    if ($checkref === true) {
        if (false === ( $ref = s_action_referer() )) {
            //没有来源，有可能是非法请求或者是flash请求
            return false;
        }

        if (false === ( $allows = function_exists('source_list') )) {
        }
    }

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
    return $_SERVER['referer'];
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


//返回一个临时文件，此文件只针对当前进程。
function s_action_file($path=false) {
    if ($path === false) {
        //随机产生一个文件
        $path = s_action_time() . '_' . rand();
    }

    if (substr($path, 0, 1) !== '/') {
        //非绝对路径，处理成绝对路径
        if (false === ( $dir = s_action_dir() )
            || s_bad_string($dir['dir'], $dir)
        ) {
            //获取默认临时目录失败
            return false;
        }

        //生成绝对路径
        $path = $dir['dir'] . $path;
    }


    //清空已存在的文件
    return false === file_put_contents($path, '') ? false : $path;
}


//返回一个临时目录，此目录只针对当前进程。
function s_action_dir($path=false, $mask=0755) {
    if ($path === false) {
        $path = defined('APP_NAME') ? APP_NAME : 'tmp';
    }

    if (s_bad_string($path)) {
        return false;
    }

    $real = false;
    //检查是否为绝对路径
    if (substr($path, 0, 1) !== '/') {
        //非绝对路径自动添加项目前缀
        if (isset($_SERVER["SINASRV_CACHE_DIR"])) {
            $real = $_SERVER["SINASRV_CACHE_DIR"] . $path;
        }

    } else {
        //绝对路径
        $real = $path;
    }


    if (!is_dir($real)
        && !mkdir($real, $mask, true)
    ) {
        return false;
    }

    return array(
        // /data1/www/cache/all.vic.sina.com.cn/disney
        // http://all.vic.sina.com.cn/cache
        "url" => $_SERVER["SINASRV_CACHE_URL"] . "/" . $path,
        "dir" => $_SERVER["SINASRV_CACHE_DIR"] . $path,
    );
}
