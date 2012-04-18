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


function s_http_response($url, &$params, $method) {
    if (s_bad_string($url)) {
        return false;
    }

    if ($params === false) {
        $params = array();
    }


    //post请求
    $method = $method === true;

    if (isset($params["cookie"])) {
        //有cookie
    }

    if (isset($params["file"])) {
        //有文件要上传
    }

    if (isset($params["image"])) {
        //有图片要上传
    }


    return false;
}


function s_http_get($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $response = s_http_response($url, $params, false) )
    ) {
        return false;
    }

    return $repsonse;
}


function s_http_post($url, &$params=false) {
    if (s_bad_string($url)
        || false === ( $response = s_http_response($url, $params, true) )
    ) {
        return false;
    }

    return $repsonse;
}


function s_http_json($url, &$params=false, $method=true) {
    if (s_bad_string($utl)) {
        return false;
    }

    $response = ( $method === true ? s_http_post($url, $params) : s_http_get($url, $params) );

    return json_decode($repsonse, true);
}
