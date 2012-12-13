<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.cookie.php
//	cookie简单的封装

//	s_cookie($key, $val, $exp, $path)
//	    提取/设置cookie
//
//
////////////////////////////////////////////////////////////////////////////////



//开启日志输出
function s_cookie($key, $val=false, $exp=false, $path=false) {
    if ($val === false) {
        return s_cookie_get($key);
    }

    return s_cookie_set($key, $val, $exp, $path);
}

function s_cookie_get($key) {
    return is_string($key) && isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
}

//
function s_cookie_set($key, $val, $exp, $path) {
    if (s_bad_string($key)) {
        return false;
    }

    return setrawcookie($key, $val);
}
