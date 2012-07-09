<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.string.php
//	处理与字符串有关的函数
//
//	s_string_length($strng, $trim=false)
//	    返回字符长度，其中中文算两个长度。如果指定$trim为true，那么会截断前后的空字符
//  
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



