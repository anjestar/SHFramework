<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.http.php
//	发送http请求的函数
//
//	s_bad_id($id)
//	    判断数字是否正确（大于0）
//  
//	s_bad_0id($id)
//	    判断数字是否正确（等于0也可以）
//  
//	s_bad_string($string)
//	    判断字符串是否正确
//
//	s_bad_array($string, &$var)
//	    判断数组否是正确，如果正确赋值给$var变量
//
//	s_bad_email($email, $var)
//	    判断邮箱地址是否正确
//
//
//
////////////////////////////////////////////////////////////////////////////////

require_once ("HTTP/Request.php");

function s_http_json(&$url, &$params, $method) {
}

function s_http_file(&$ulr, &$file) {
}

////////////////////////////////////////////////////////////////////////////////
//  $params = array(
//      "file"      => array("image" => "/tmp/upload/tm001.png", "file" => $file),
//      "cookie"    => array("SUE" => "ad2sadadaeadadasel;lkjj;", "SUP" => "asdhasdaesadas"),
//      "header"    => array("SUE" => "ad2sadadaeadadasel;lkjj;", "SUP" => "asdhasdaesadas"),
//  )
//
//
function s_http(&$url, $method, &$params) {
    if (s_bad_string($url)
        || s_bad_array($params)
    ) {
        return false;
    }


	$curl = curl_init();

    $options = array(
        CURLOPT_URL     => $url,

    );

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8");

    if ($method === "get") {
        //GET请求
        curl_setopt($curl , CURLOPT_GET, true);

    } else {
        //POST请求
        curl_setopt($curl , CURLOPT_POST, true);

        if (!s_bad_array($params["file"], $files)) {

            curl_setopt($curl , CURLOPT_POSTFILEDS, $files);
        }
    }

    if (!s_bad_array($params["cookie"], $cookie)) {
        curl_setopt($curl , CURLOPT_COOKIE, s_http_encode($cookie));
    }

    if (s_bad_0array($params["header"], $header)) {
        $header = array(
        );
    }

    curl_setopt($curl, CURLOPT_USERAGENT, “Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8″);

        curl_setopt($curl , CURLOPT_COOKIE, s_http_encode($cookie));

    //区分是GET还是POST
}

    $file = array("upimg"=>"@E:/png.png");//文件路径，前面要加@，表明是文件上传.  
    $curl = curl_init("http://localhost/a.php");  
        curl_setopt($curl,CURLOPT_POST,true);  
        curl_setopt($curl,CURLOPT_POSTFIELDS,$file);  
            curl_exec($curl);  
