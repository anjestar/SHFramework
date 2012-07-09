<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.http.php
//	通过http请求获取数据的相关函数
//
//	s_http_response($url, &$params, $method)
//	    返回一个http response
//	    参数中$params["_name"]和$params["_data"]分别给上传二进制数据准备，其它数据不可占用。
//	    参数中文件路径
//  
//	s_http_get($url, &$params=false)
//	    返回通过get获取的response对象
//  
//	s_http_post($url, &$params=false)
//	    返回通过post获取的response对象
//
//	s_http_json($url, &$params=false, $method=true)
//	    以jsonr对象返回数据
//
//
//
////////////////////////////////////////////////////////////////////////////////


function s_http_response($url, &$params=false, $method="get") {
    if (s_bad_string($url)) {
        return false;
    }

    if ($params === false) {
        $params = array();
    }


    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_VERBOSE, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    }

    if (isset($params["cookie"])) {
        //有cookie
        $arr = array();

        foreach($params["cookie"] as $key => $value) {
            $arr[] = $key . "=" . rawurlencode($value);
        }

        curl_setopt($curl, CURLOPT_COOKIE, implode(";", $arr)); 

        unset($params["cookie"]);
    }

    ////////////////////////////////////////////////////////////////////////////////
    //特殊变量（_username,和_password)，用于给http添加用户名及密码
    //
    $userpass = "";
    if (isset($params['_username'])) {
        $userpass = $params['_username'];
    }

    if (isset($params['_password'])) {
        $userpass = $userpass . ':' . $params['_username'];
    }

    if ($userpass) {
        //有用户名与密码添加到http头部
        curl_setopt($curl, CURLOPT_USERPWD, $userpass);
    }

    //
    ////////////////////////////////////////////////////////////////////////////////


    //将余下的post字段添加到http请求中
    $arr = array();

    if ($method === "get") {
        //GET
        foreach ($params as $key => &$value) {
            if (is_scalar($value)) {
                $arr[] = $key . "=" . rawurlencode($value);
            }

            unset($value);
        }

        $url .= ( strrpos($url, "?") === false ? "?" : "" ) . implode("&", $arr);

    } else if ($method === "post") {
        //POST
        curl_setopt($curl, CURLOPT_POST, 1);

        if (isset($params["_name"])
            && isset($params["_data"])
        ) {
            //有图片数据提交
            _s_http_post1($curl, $params);

        } else {
            //简单数据提交
            _s_http_post2($curl, $params);
        }
    }

    //加载URL
    curl_setopt($curl, CURLOPT_URL, $url);
    $ret = curl_exec($curl);

    curl_close($curl);

    //var_dump($params);
    //var_dump($ret);

    return $ret;
}


function _s_http_post1(&$curl, &$params) {
    $boundary = uniqid('------------------');

    $start    = '--' . $boundary;
    $end      = $start . '--';
    $body     = '';

    //图片数据有$params["_name"]和$params["_data"]变量
    $mpheader = array("Content-Type: multipart/form-data; boundary={$boundary}" , "Expect: ");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $mpheader);

    //二进制数据（图片）
    $body .= $start . "\r\n";
    $body .= 'Content-Disposition: form-data; name="' . $params["_name"] . '"; filename="wiki.jpg"' . "\r\n";
    $body .= 'Content-Type: image/jpg'. "\r\n\r\n";
    $body .= $params["_data"] . "\r\n";


    unset($params["_name"]);
    unset($params["_data"]);


    //余下就是一般字符串数据
    foreach ($params as $name => &$value) {
        //一般字符串
        $body .= $start . "\r\n";
        $body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
        $body .= $value . "\r\n";

        unset($value);
    }

    $body .= "\r\n". $end;

    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
}


function _s_http_post2(&$curl, &$params) {
    $posts = array();

    foreach ($params as $name => &$value) {
        $posts[] = $name . "=" . rawurlencode($value);

        unset($value);
    }

    curl_setopt($curl, CURLOPT_POSTFIELDS, implode("&", $posts));

    unset($posts);
}


function s_http_get($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $response = s_http_response($url, $params, "get") )
    ) {
        return false;
    }

    return $repsonse;
}


function s_http_post($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $response = s_http_response($url, $params, "post") )
    ) {
        return false;
    }

    return $repsonse;
}


function s_http_json($url, &$params=false, $method="get") {
    if (s_bad_string($url)
        || false === ( $response = s_http_response($url, $params, $method) )
    ) {
        return false;
    }

    return json_decode($response, true);
}

