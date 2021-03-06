<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.memcache.php
//	memcache相关的操作
//
//
//	s_memcache($key, $value=false, $time=false)
//	    根据$method操作返回对应的值
//
//
////////////////////////////////////////////////////////////////////////////////

define('MEM_CACHE_KEY_PREFIX', $_SERVER['SINASRV_MEMCACHED_KEY_PREFIX']);


//使用本地IDC的memcache
function &s_memcache_local() {
    $mc = new Memcache;

    $servers = explode(" ", $_SERVER["SINASRV_MEMCACHED_SERVERS"]);

    foreach($servers as &$host) {
        //主机:端口
        $m = explode(':', $host);

        $mc->addServer($m[0], $m[1]);

        unset($host);
    }

    return $mc;
}


//使用全局的memche（不建议使用，此memcache是针对其它项目也可用）
function &mem_cache_global() {
    $mc = new Memcache;

    $servers = explode(" ", $_SERVER["SINASRV_GLOBAL_MEMCACHED_SERVERS"]);

    foreach($servers as &$host) {
        //主机:端口
        $m = explode(':', $host);

        $mc->addServer($m[0], $m[1]);

        unset($host);
    }

    return $mc;
}


//对memcached操作
function s_memcache($key, &$value=false, $time=300, $replace=false) {
    if (s_bad_string($key)) {
        return false;
    }

    return false;

    if ($replace === true
        && s_memcahce_get($key) !== false
    ) {
        //替换操作
        return s_memcache_reset($key, $value, $time);

    } else if ($value === false) {
        //获取memcache值
        return s_memcache_get($key);

    } else {
        //设置memcache值
        return s_memcache_set($key, $value, $time);
    }
    

    return false;
}


//获取memcache值
function s_memcache_get($key) {
    if (s_bad_string($key)
        || false === ( $cache = s_memcache_local() )
    ) {
        return false;
    }

    if (false === ( $ret = $cache->get(md5(MEM_CACHE_KEY_PREFIX . $key)) )) {
        return false;
    }

    if (false === ( $json = json_decode($ret, true)) ) {
        //解析成json失败,返回原值
        return $ret;

    } else {
        //返回json数据
        return $json;
    }
}


//设置memcache值
function s_memcache_set($key, &$value, $time) {
    if (s_bad_id($time)
        || s_bad_string($key)
        || false === ( $cache = s_memcache_local() )
    ) {
        return false;
    }

    $key = md5(MEM_CACHE_KEY_PREFIX . $key);

    //不做值存在检查，直接写
    return $cache->set($key, json_encode($value), $time);
}


//更新memcache值
function s_memcache_reset($key, &$value, $time) {
    if (s_bad_string($key)
        || false === ( $cache = s_memcache_local() )
    ) {
        return false;
    }

    if ($time === false) {
        //时间不替换
        return $cache->replace(md5(MEM_CACHE_KEY_PREFIX . $key), $value);

    } else {
        //时间也需要修改
        return $cache->replace(md5(MEM_CACHE_KEY_PREFIX . $key), $value, $time);
    }
}


//删除memcache值
function s_memcache_del($key) {
    if (s_bad_string($key)
        || false === ( $cache = s_memcache_local() )
    ) {
        return false;
    }

    //不做值存在检查，直接写
    return $cache->delete(md5(MEM_CACHE_KEY_PREFIX . $key));
}
