<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.memcache.php
//	memcache相关的操作
//
//
//	s_memcache($key, $method="get", $value=false)
//	    根据$method操作返回对应的值
//
//
////////////////////////////////////////////////////////////////////////////////


//对memcached操作
function s_memcache($key, $value=false, $time=300) {
    return false;

    if (s_bad_string($key)) {
        return false;
    }

    if ($value === false) {
        //获取memcache值
        return s_memcache_get($key);

    } else if ($method === "set") {
        //设置memcache值
        return s_memcache_set($key, $value, $time);
    }

    
    return false;
}

//获取memcache值
function s_memcache_get($key) {
}

//设置memcache值
function s_memcache_set($key, $value, $time) {
}


//递增memcache值
function s_memcache_inc($key, $step=1) {
}
