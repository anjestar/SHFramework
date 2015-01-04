<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.badges.php
//	勋章接口
//
//      s_badges_new($uid, $username, $password)
//	        用户获取勋章
//
//  
//
//
////////////////////////////////////////////////////////////////////////////////



//订阅直播
function s_badge_new($uid, $bid, $username, $password) {
    if (s_bad_id($uid)
        || s_bad_string($username)
        || s_bad_string($password)
    ) {
        return s_err_arg();
    }


    $key = "badge_new_by#uid={$uid}&bid={$bid}&user={$username}&password={$password}";

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'badge_id'  => $bid,
            'uids'      => $uid,
            '_username' => $username,
            '_password' => $password,
        );

        if (false === ( $data = s_badge_http('http://api.weibo.com/2/proxy/badges/issue.json?source=' . APP_KEY, $data, 'post') )) {
            return false;
        }

        //缓存一小时
        s_memcache($key, $data, 300);
    }

    return $data;
}


//返回以json格式的直播数据，此处为做errno或errmsg检查
function s_badge_http($url, $params=false, $method='post') {
    if (false === $params) {
        $params = array();
    }

    if (false === ( $data = s_http_json($url, $params, $method) )) {
        return false;
    }

    return $data;
}
