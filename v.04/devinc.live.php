<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.live.php
//	直播接口
//
//      s_live_list_by($wid)
//	        判断数字是否正确（大于0）
//
//  
//      s_live_watch(&$user)
//          订阅直播
//
//
//      s_live_info(&$user)
//          获取直播信息
//
//
//      s_live_content(&$user, $page=1)
//          获取直播内容区微博
//
//
//      s_live_ask(&$user, $page=1)
//          获取直播用户提问
//
//
//      s_live_post(&$user, &$mids, $act=0)
//          发布微直播
//
//
//      s_live_list($page=1, $type=1)
//          获取直播列表
//
//
////////////////////////////////////////////////////////////////////////////////



//订阅直播
function s_live_watch(&$user, $lid) {
    if (s_bad_array($user)
        || s_bad_array($lid)
    ) {
        return s_err_arg();
    }


    $key = 'live_watch_by_lid#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid' => $user['id'],
            'lid' => $lid,
            'act' => 0,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/subscribelive.php', $data, 'post') )) {
            return s_err_sdk();
        }

        //缓存一小时
        s_memcache($key, $data, 3600);
    }

    return s_live_filter($data);
}


//获取直播信息
function s_live_info(&$user, $lid, $url=false) {
    if (s_bad_array($user)
        || s_bad_id($lid)
    ) {
        return s_err_arg();
    }

    if ($url !== false) {
        //根据url查询直播信息
        return s_live_info_by_url($user, $url);
    }


    $key = 'live_info_by_lid#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'    => $user['id'],
            'lid'    => $lid,
            'detail' => 1,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getliveinfo.php', $data) )) {
            return s_err_sdk();
        }

        //缓存一小时
        s_memcache($key, $data, 3600);
    }

    return s_live_filter($data);
}


//根据url获取直播信息
function s_live_info_by_url(&$user, &$url) {
    if (s_bad_id($lid)
        || s_bad_array($user)
        || s_bad_string($url)

    ) {
        return s_err_arg();
    }


    $key = 'live_info_by_url#' . $url;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'url'    => $user['id'],
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/live/live/getLiveInfoByUrl.php', $data) )) {
            return s_err_sdk();
        }

        //缓存一小时
        s_memcache($key, $data, 3600);
    }

    return $data;
}



//获取直播内容区微博
function s_live_content(&$user, $lid, $page=1, $pagesize=20) {
    if (s_bad_id($lid)
        || s_bad_id($page)
        || s_bad_id($pagesize)

        || s_bad_array($user)
    ) {
        return s_err_arg();
    }


    $key = 'live_content_by_lid#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['id'],
            'lid'       => $lid,
            'page'      => $page,
            'pagesize'  => $pagesize,
        );


        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getlivecontents.php', $data))) {
            return s_err_sdk();
        }
        echo "set";

        //缓存10秒钟
        s_memcache($key, $data, 10);
    }
        var_dump($data);

    if (isset($data['result'])
        && count($data['result'])
    ) {
        //有数据
        $mids = array();

        foreach ($data['result'] as &$item) {
            $mids[] = $item['mid'];

            unset($item);
        }

        $data['list'] = s_weibo_detail_by_mids($mids);

        unset($data['result']);
    }


    return $data;
}


//获取互动区的直接内容
function s_live_weibo(&$user, $lid, $page=1) {
    if (s_bad_id($lid)
        || s_bad_id($page)
        || s_bad_array($user)
    ) {
        return s_err_arg();
    }


    $key = 'live_weibo_by_#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['id'],
            'liveid'    => $lid,
            'page'      => $page,
            'pagesize'  => 20,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getLiveInteraction.php', $data))) {
            return s_err_sdk();
        }

        //缓存5秒钟
        s_memcache($key, $data, 5);
    }
    var_dump($$data);

    return $data;
}


//获取直播用户提问
function s_live_ask(&$user, $lid, $page=1) {
    if (s_bad_id($lid)
        || s_bad_id($page)
        || s_bad_array($user)
    ) {
        return s_err_arg();
    }


    $key = 'live_ask_by_#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['id'],
            'lid'       => $lid,
            'page'      => $page,
            'pagesize'  => 10,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getquestionlist.php', $data))) {
            return s_err_sdk();
        }

        //缓存5秒钟
        s_memcache($key, $data, 5);
    }

    return $data;
}


//发布微直播
function s_live_post(&$user, &$mids, $act=0) {
    if (s_bad_array($user)
        || s_bad_id($act)
        || s_bad_string($mids)
    ) {
        return s_err_arg();
    }



    $data = array(
        'uid' => $user['id'],
        'lid' => $lid,
        'mid' => $mids,
        'act' => $act,
    );

    if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/setmblogstatus.php', $data, 'post'))) {
        return s_err_sdk();
    }

    return $data;
}



//获取直播的用户信息
function s_live_user(&$user, $lid) {
    if (s_bad_array($user)) {
        return s_err_arg();
    }


    $key = 'live_user_by_lid#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['id'],
            'lid'       => $lid,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getliveusers.php', $data) )) {
            return s_err_sdk();
        }

        //缓存一小时
        s_memcache($key, $data, 3600);
    }

    return $data;
}


//获取直播列表
function s_live_now($page=1, $type=1) {
    if (false === ( $user = s_action_user(false) )) {
        return s_err_arg();
    }


    $key = 'live_list_by_lid#' . $lid;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['id'],
            'type'      => $type,
            'page'      => $page,
            'pagesize'  => 10,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getlivelist.php', $data) )) {
            return s_err_sdk();
        }

        //缓存30秒
        s_memcache($key, $data, 30);
    }

    return $data;
}


//返回以json格式的直播数据，此处为做errno或errmsg检查
function s_live_http($url, $params=false, $method="get") {
    if (false === $params) {
        $params = array();
    }

    if (false === ( $data = s_http_json($url, $params, $method) )
        || !isset($data['errno'])
        || $data['errno'] != 1
    ) {
        return false;
    }

    return $data;
}


//返回以此框架标准的error值
function s_live_filter(&$result) {
    if (s_bad_array($result)) {
        return array();
    }

    $result['error'] = 0;

    if (isset($result['result'])) {
        foreach ($result['result'] as $key=> &$value) {
            $result[$key] = $value;

            unset($value);
        }

        unset($result['result']);
    }


    return $result;
}
