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


function s_http_response($url, &$params=false, $method="get", $mutil=false, $cookie=false, $header=false, $userpwd=false) {
    if (s_bad_string($url)) {
        return false;
    }

    if ($params === false) {
        $params = array();
    }

    if ($header === false) {
        $header = array();
    }


    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER,          FALSE);
    curl_setopt($curl, CURLOPT_VERBOSE,         FALSE);
    curl_setopt($curl, CURLINFO_HEADER_OUT,     TRUE);
    curl_setopt($curl, CURLOPT_HTTP_VERSION,    CURL_HTTP_VERSION_1_0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER,  TRUE);


    if ($userpwd) {
        //有用户名与密码添加到http头部
        curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
    }

    if (isset($_SERVER['HTTP_REFERER'])) {
        curl_setopt($curl, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
    }

    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    }


    if ($cookie === true) {
        //携带cookie
        foreach($_COOKIE as $key => $value) {
            $arr[] = $key . "=" . rawurlencode($value);
        }

        $header[] = 'Cookie: ' .  implode('; ', $arr);
    }



    switch(strtolower($method)) {
        case 'get':
            if (false === strpos('?', $url)) {
                $url .= '?';
            }

            $url .= '&' . http_build_query($params);

            break;

        default:
            curl_setopt($curl, CURLOPT_POSTFIELDS, s_http_boundary($params, $header));
    }



    //加载URL
    curl_setopt($curl, CURLOPT_URL,             $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER,      $headers);

    $ret    = curl_exec($curl);
    $code   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $info   = curl_getinfo($curl);

    curl_close($curl);

    if ($params['debugger'] === true) {
        echo "\nresponse:\n";
        var_dump($ret);

        echo "\ncode:\n";
        var_dump($code);

        echo "\ninfo:\n";
        var_dump($code);
    }

    return $ret;
}


function s_http_boundary(&$params, &$header) {
    if (!is_array($params)) {
        $params = array();
    }

    if (!is_array($header)) {
        $header = array();
    }


    $header[] = "Content-Type: multipart/form-data; boundary={$boundary}";

    uksort($params, 'strcmp');


    $body           = '';
    $boundary       = uniqid('------------------');
    foreach ($params as $key => $value) {
        if(in_array($key, array('pic', 'image')) && $value{0} == '@') {
            $content    = file_get_contents(ltrim($value, '@'));

            $body       .= '--' . $boundary . "\r\n";
            $body       .= 'Content-Disposition: form-data; name="' . $key . '"; filename="' . basename($value) . '"'. "\r\n";
            $body       .= "Content-Type: image/unknown\r\n\r\n";
            $body       .= $content. "\r\n";

        } else {
            $body       .= '--' . $boundary . "\r\n";
            $body       .= 'content-disposition: form-data; name="' . $key . "\"\r\n\r\n";
            $body       .= $value."\r\n";
        }
    }

    //end
    $body .= '--' . $boundary . '--';

    return $body;
}

function s_http_get($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $rep = s_http_response($url, $params, "get") )
    ) {
        return false;
    }

    return $rep;
}


function s_http_post($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $rep = s_http_response($url, $params, "post") )
    ) {
        return false;
    }

    return $rep;
}


function s_http_json($url, &$params=false, $method="get") {
    if (s_bad_string($url)
        || false === ( $rep = s_http_response($url, $params, $method) )
    ) {
        return false;
    }

    return json_decode($rep, true);
}

