<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.string.php
//	处理与字符串有关的函数
//
//	s_string_length($strng, $trim=false)
//	    返回字符长度，其中中文算两个长度。如果指定$trim为true，那么会截断前后的空字符
//  
//	s_string_safe($string)
//	    返回防止SQL注入的字符串
//  
//	s_string_2dir($string)
//	    返回根据$string创建目录是否成功的状态
//  
//
////////////////////////////////////////////////////////////////////////////////


//返回字符串长度
function s_string_length($string, $trim=false) {
    if (s_bad_string($string, $string, $trim)) {
        return false;
    }

    $len1 = strlen($string);
    $len2 = mb_strlen($string);

    return $len1 === $len2 ? $len1 : $len2;
}



//取得where后所有的条件语句(where a=1 and b=1 and c=1 order by xx group by yy limit 10)
function s_string_safe($string, $trim=false) {
    if (s_bad_string($string, $string, $trim)) {
        return false;
    }

    $string = str_replace("'", "\'", $string);
    $string = str_replace('"', '\"', $string);
    $string = str_replace('>', '\>', $string);
    $string = str_replace('<', '\<', $string);
    $string = str_replace('\\', '\\\\', $string);

    return $string;
}


//将字符串创建成目录
function s_string_2dir($path, $mask=0755) {
    if (s_bad_string($path)) {
        return false;
    }

    if (isset($_SERVER["SINASRV_CACHE_DIR"])) {
        $real = $_SERVER["SINASRV_CACHE_DIR"] . $path;
    }

    if (!is_dir($real)
        && !mkdir($real, $mask, true)
    ) {
        return false;
    }

    return array(
        // /data1/www/cache/all.vic.sina.com.cn/
        // http://all.vic.sina.com.cn/cache
        "url" => $_SERVER["SINASRV_CACHE_URL"] . "/" . $path,
        "dir" => $_SERVER["SINASRV_CACHE_DIR"] . $path,
    );
}


function s_float_value($float, $int=true) {
    if (is_float($float)) {
        $float = sprintf("%f", $float);
    }

    if ($int !== true
        || false === ( $pos = strpos($float, '.') )
    ) {
        //不做整形处理或没有小数点
        return $float;
    }

    return substr($float, 0, $pos);
}
