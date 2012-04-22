<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.http.php
//	通过http请求获取数据的相关函数
//
//	s_http_response($url, &$params, $method)
//	    返回一个http response
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


function s_http_response($url, &$params=false, $method=true) {
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
            $arr[] = $key . "=" . urlencode($value);
        }

        curl_setopt($curl, CURLOPT_COOKIE, implode(";", $arr)); 

        unset($params["cookie"]);
    }

    if (isset($params["file"])) {
        //有文件要上传
        curl_setopt($curl, CURLOPT_POST, 1);

        foreach ($params["file"] as &$value) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params["file"]);

            unset($value);
        }

        unset($params["file"]);
    }


    //将余下的post字段添加到http请求中
    $arr = array();

    foreach ($params as $key => $value) {
        $arr[] = $key . "=" . urlencode($value);
    }

    if ($method === "get") {
        //GET
        $url .= ( strrpos($url, "?") === false ? "?" : "" ) . implode("&", $arr);

    } else {
        //POST
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, implode("&", $arr));
    }

    //加载URL
    curl_setopt($curl, CURLOPT_URL, $url);
    $ret = curl_exec($curl);

    curl_close($curl);

    return $ret;
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
    if (s_bad_string($url)) {
        return false;
    }

    $response = s_http_response($url, $params, $method);

    return json_decode($response, true);
}
