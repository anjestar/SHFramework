<?php
////////////////////////////////////////////////////////////////////////////////
// devinc.cookie.php
//	cookie简单的封装

//	s_cookie($key, $val, $exp, $path)
//	    提取/设置cookie
//
//
////////////////////////////////////////////////////////////////////////////////


//Cookie的配置文件
define('SSOCOOKIE_CONFIG',  'cookie.conf');

//Cookie的配置文件32位的键
define('SSOCOOKIE_KEY32_1', 'v0');

//Cookie的配置文件32位的键
define('SSOCOOKIE_KEY32_2', 'v1');

//Cookie的配置文件32位的键
define('SSOCOOKIE_KEY32_3', 'rv');

//Cookie的配置文件1024位的键
define('SSOCOOKIE_KEY1024', 'rv0');


function s_cookie_conf($config=false) {
    if ($config === false) {
        $config = SSOCOOKIE_CONFIG;
    }

    if (!( $config = @parse_ini_file($config) )
        || !isset($config[SSOCOOKIE_KEY32_1])
        || !isset($config[SSOCOOKIE_KEY32_2])
        || !isset($config[SSOCOOKIE_KEY32_3])
        || !isset($config[SSOCOOKIE_KEY1024])
    ) {
        return false;
    }

    return $config;
}



//开启日志输出
function s_cookie($key, $val=false, $exp=false, $path="/", $secure=false) {
    if ($val === false) {
        return s_cookie_get($key);
    }

    return s_cookie_set($key, $val, $exp, $path, $secure);
}

function s_cookie_get($key) {
    return is_string($key) && isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
}

//
function s_cookie_set($key, $val, $exp=0, $path="/", $secure=false) {
    if (s_bad_string($key)) {
        return false;
    }

    if (s_bad_string($val)) {
        $val = strval($val);
    }

    if (s_bad_id($exp)) {
        $exp = false;
    }

    //return setrawcookie($key, $val);
    return setcookie($key, $val, $exp ? $exp + s_action_time() : false, $path, $secure);
}


//返回1024的私钥
function s_cookie_pki() {
    $conf = s_cookie_conf();

    return isset($conf[SSOCOOKIE_KEY1024]) ? $conf[SSOCOOKIE_KEY1024] : false;
}


//返回用户的SUE值
function s_cookie_sue(&$user) {
    if (s_bad_array($user)
        || !( $pki = s_cookie_pki() )
    ) {
        return false;
    }

    return 'es=' . md5(s_cookie_sup($user) . md5($pki));
}


//返回用户的sep值
function s_cookie_sup(&$user) {
    if (s_bad_array($user)
        || !( $conf = s_cookie_conf() )
    ) {
        return false;
    }

    $time = s_action_time();

    //begintime
    return "bt=" . strval($time)
        //expiredtime
        . "&et=" . strval($time + 7 * 86400)
        //userid
        . "&uid=" . $user['id']
        //nickname
        . "&nn="  . $user['nickname']
        //encryptversion
        . "&ev="  . $conf[SSOCOOKIE_KEY32_1];
}


function s_cookie_desue() {
    parse_str($_COOKIE['SUE'], $sues);
    parse_str($_COOKIE['SUP'], $sups);

    if (!isset($sups['et'])
        || !isset($sues['es'])

        || !( $pki = s_cookie_pki() )

        || $sups['et'] < s_action_time()
        || $sues['es'] !== md5($_COOKIE['SUP'] .  md5($pki))
    ) {
        return false;
    }

    return $sups;
}
