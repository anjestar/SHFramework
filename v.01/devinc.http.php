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
            $arr[] = $key . "=" . urlencode($value);
        }

        curl_setopt($curl, CURLOPT_COOKIE, implode(";", $arr)); 

        unset($params["cookie"]);
    }

    //将余下的post字段添加到http请求中
    $arr = array();

    if ($method === "get") {
        //GET
        foreach ($params as $key => &$value) {
            if (is_scalar($value)) {
                $arr[] = $key . "=" . urlencode($value);
            }

            unset($value);
        }

        $url .= ( strrpos($url, "?") === false ? "?" : "" ) . implode("&", $arr);

    } else if ($method === "post") {
        //POST

        //post头部开始
        //$streamed = false;
        //$boundary = uniqid('------------------');

        //$start    = '--' . $boundary;
        //$end      = $start . '--';
        //$body     = '';

        curl_setopt($curl, CURLOPT_POST, 1);

        $files  = array();
        $fileds = array();


        foreach ($params as $name => &$value) {
            /*
            if (substr($value, 0, 1) === '@') {
                //二进制数据（图片）
                $body .= $start . "\r\n";
                $body .= 'Content-Disposition: form-data; name="' . $name . '"; filename="wiki.jpg"' . "\r\n";
                $body .= 'Content-Type: image/jpg'. "\r\n\r\n";
                $body .= file_get_contents(substr($value, 1)) . "\r\n";

                $streamed = true;

            } else {
                //一般字符串
                $body .= $start . "\r\n";
                $body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
                $body .= $value . "\r\n";
            }
             */

            if (substr($value, 0, 1) === '@') {
                //二进制文件
                $files[$name] = $value;

            } else {
                $fileds[] = $name . '=' . $value;
            }

            unset($value);
        }

        if (count($files)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $files);
        }

        if (count($fileds)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $fileds));
        }


        //$body .= "\r\n". $end;

        //echo var_dump($body);

        //curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);

        //echo curl_error($curl);


        //if ($streamed) {
            //$mpheader = array("Content-Type: multipart/form-data; boundary={$boundary}" , "Expect: ");
            //curl_setopt($curl, CURLOPT_HTTPHEADER, $mpheader);
        //}
    }

    //加载URL
    curl_setopt($curl, CURLOPT_URL, $url);
    $ret = curl_exec($curl);

    curl_close($curl);

    return $ret;


    //是json格式，做检查
    //if ('json' === substr($ret, strrpos($ret, '.') + 1) ) {
        if (false === ( $pos1 = strpos($ret, '{') )
            || false === ( $pos2 = strrpos($ret, '}') ) 
        ) {
            return false;
        }

        return substr($ret, $pos1, $pos2);
    //}

    //return $ret;
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
