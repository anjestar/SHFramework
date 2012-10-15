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
//	s_string_subject(&$strng)
//	    返回已替换之后的主题字符串
//
//
//	s_string_face(&$strng)
//	    返回已替换之后的表情字符串
//
//
//	s_string_turl(&$strng)
//	    返回已替换之后的短链字符串
//
//
//	s_string_at(&$strng)
//	    返回已替换之后的@用户名字符串
//
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


//指定长度的随机字符串
function s_string_random($len=32, $less=true) {
    if (s_bad_id($len)) {
        $len = 32;
    }

    $len    = 64;
    $token  = '';

    $chars  = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 
    );

    if (!$less) {
        $arr1 = array(
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            '~', '@', '#', '$', '%', '^', '&', '*', '(', ')', 
            '-', '+', '=', '[', ']', ';', '/', '?',
        );

        $chars = array_merge($chars, $arr1);
    }


    //打扰数组
    shuffle($chars);

    for ($i=0, $l=count($chars); $i<$len; ++$i) {
        $output .= $chars[mt_rand(0, $l)];
    }

    return $output;
}
