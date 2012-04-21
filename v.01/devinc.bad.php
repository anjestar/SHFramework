<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.bad.php
//	判断错误的函数，参数错误返回true，正确返回false
//
//  s_bad_id($id)
//	    判断数字是否正确（大于0）
//  
//  s_bad_0id($id)
//	    判断数字是否正确（等于0也可以）
//  
//  s_bad_string($string)
//	    判断字符串是否正确
//
//  s_bad_array($string, &$var)
//	    判断数组否是正确，如果正确赋值给$var变量
//
//  s_bad_email($email, $var)
//	    判断邮箱地址是否正确
//
//  s_bad_int($key, $var=false, $method="post")
//      判断POST中的$key是否为正确（$method可以为post或get）
//
//  s_bad_int($key, $var=false, $method="post")
//    判断POST中的$key是否为正确（$method可以为post或get）
//
//
////////////////////////////////////////////////////////////////////////////////


function s_bad_id(&$id, &$var=false) {
    if(!is_numeric($id)
        || ( $id = intval($id) ) <= 0
    ) {
        return true;
    }

    if ($var !== false) {
        $var = $id;
    }

    return false;
}


function s_bad_0id(&$id, &$var=false) {
    if(!is_numeric($id)
        || ( $id = intval($id) ) < 0
    ) {
        return true;
    }

    if ($var !== false) {
        $var = $id;
    }

    return false;
}


function s_bad_string(&$str, &$var=false, $trim=true) {
    if (!is_string($str)
        || $str !== strval($str)
        || empty($str)
    ) {
        return true;
    }

    if ($var !== false) {
        $var = strval($str);
    }

    if ($trim) {
        $var = trim($var);
    }

    return false;
}


function s_bad_0string(&$str, &$var=false) {
    if (!is_string($str)
        || $str !== strval($str)
    ) {
        return true;
    }

    if ($var !== false) {
        $var = $str;
    }

    return false;
}

function s_bad_array(&$arr, &$var=false) {
    if (!is_array($arr)
        || empty($arr)
    ) {
        return true;
    }

    if ($var !== false) {
        $var = $arr;
    }

    return false;
}


function s_bad_email(&$email, &$var=false) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return true;
    }

    if ($var !== false) {
        $var = $email;
    }

    return false;
}


function s_bad_key(&$obj, &$key, &$var=false) {
    if ($var === false) {
        return !isset($obj[$key]);
    }

    if (isset($obj[$key]) {
        $var = $obj[$key];
    }

    return true;
}


function s_bad_int($key, &$var=false, &$method="post") {
    $values = false;

    if ($method === "post") {
        $method = &$_POST;

    } else if ($method === "get") {
        $method = &$_GET;
    }

    if ($values === false) {
        return false;
    }


    return s_bad_id($values[$key], $var);
}


function s_bad_text($key, &$var=false, &$method="post") {
    $values = false;

    if ($method === "post") {
        $method = &$_POST;

    } else if ($method === "get") {
        $method = &$_GET;
    }

    if ($values === false) {
        return false;
    }


    return s_bad_string($values[$key], $var);
}
