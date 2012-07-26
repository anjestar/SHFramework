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


//替换微主题中的字符。将#随便#换成<a href="http://s.weibo.com/weibo/随便"
function s_string_subject(&$weibo) {
    if (s_bad_string($weibo)) {
        return false;
    }

    return preg_replace("/\#(.*)\#/iUs", "<a href=\"http://s.weibo.com/weibo/$1\" title=\"$1\" target='_blank'>$0</a>", $weibo); 
}


//替换微博文字中的表情。将[开心]替换成<img src="http://weibo.com/face/231.gif />"
function s_string_face(&$weibo) {
    if (s_bad_string($weibo)) {
        return false;
    }

    return $weibo;
    //return preg_replace("/\#(.*)\#/iUs", "<a href=\"http://s.weibo.com/weibo/$1\" target=\"_blank\">$0</a>", $weibo); 
}


//替换微博文字中的微连接。将http://t.cn/ABcdafd 替换成<a href="http://t.cn/ABcdafd">http://t.cn/ABcdafd</a>
function s_string_turl(&$weibo) {
    if (s_bad_string($weibo)) {
        return false;
    }

    return preg_replace("/(http\:\/\/t\.cn\/\w{7})/iUs", "<a action-type=\"feed_list_url\" mt=\"url\" href=\"$1\" title=\"$1\" target=\"_blank\">$1</a>", $weibo); 
}


//替换微博系统中的@用户名。将@啊段的马甲 替换成<a href="http://weibio.com/n/啊段的马甲">啊段的马甲</a>
function s_string_at(&$weibo) {
    if (s_bad_string($weibo)) {
        return false;
    }

    return preg_replace("/\@([\w_\x{4e00}-\x{9fa5}]+)/u", "<a usercard=\"name=$1\" title=\"$0\" href=\"http://weibo.com/n/$1\">$0</a>", $weibo); 
}

