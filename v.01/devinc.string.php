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
    if (s_bad_string($path)
        || s_bad_id($mask)
    ) {
        return false;
    }


    return is_dir($path) ? true : mkdir($path, $mask, true);
}
