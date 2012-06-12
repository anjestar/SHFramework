<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.live.php
//	直播接口
//
//  s_live_list_by($wid)
//	    判断数字是否正确（大于0）
//  
//
//
////////////////////////////////////////////////////////////////////////////////



//获取直播列表
function s_live_watch(&$user) {
    if (s_bad_array($user)) {
        return s_err_arg();
    }


    $key = 'live_watch_by_lid#' . APP_LIVEID;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid' => $user['uid'],
            'lid' => APP_LIVEID,
            'act' => 0,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/subscribelive.php', $data, 'post') )) {
            return s_err_sdk();
        }

        //缓存
        s_memcache($key, $data);
    }

    return $data;
}


//获取直播信息
function s_live_info(&$user) {
    if (s_bad_array($user)) {
        return s_err_arg();
    }


    $key = 'live_info_by_lid#' . APP_LIVEID;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'    => $user['uid'],
            'lid'    => APP_LIVEID,
            'detail' => 1,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getlivelist.php', $data) )) {
            return s_err_sdk();
        }

        //缓存
        s_memcache($key, $data);
    }

    return $data;
}


//获取直播用户信息，包括主播、嘉宾、管理员
function s_live_content(&$user, $page=1) {
    if (s_bad_id($page)
        || s_bad_array($user)
    ) {
        return s_err_arg();
    }


    $key = 'live_contents_by_#' . APP_LIVEID;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['uid'],
            'lid'       => APP_LIVEID,
            'page'      => $page,
            'pagesize'  => 20,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getlivecontents.php', $data))) {
            return s_err_sdk();
        }

        //缓存
        s_memcache($key, $data);
    }

    return $data;
}


//获取直播用户提问
function s_live_ask(&$user, $page=1) {
    if (s_bad_id($page)
        || s_bad_array($user)
    ) {
        return s_err_arg();
    }


    $key = 'live_ask_by_#' . APP_LIVEID;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['uid'],
            'lid'       => APP_LIVEID,
            'page'      => $page,
            'pagesize'  => 10,
        );

        if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/getquestionlist.php', $data))) {
            return s_err_sdk();
        }

        //缓存
        s_memcache($key, $data);
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
        'uid' => $user['uid'],
        'lid' => APP_LIVEID,
        'mid' => $mids,
        'act' => $act,
    );

    if (false === ( $data = s_live_http('http://i.service.t.sina.com.cn/sapps/live/setmblogstatus.php', $data, 'post'))) {
        return s_err_sdk();
    }

    return $data;
}


//获取直播列表
function s_live_list($page=1, $type=1) {
    if (false === ( $user = s_action_user(false) )) {
        return s_err_arg();
    }


    $key = 'live_list_by_lid#' . APP_LIVEID;

    if (false === ( $data = s_memcache($key) )) {
        $data = array(
            'uid'       => $user['uid'],
            'page'      => $page,
            'pagesize'  => 10,
        );

        if (false === ( $data = s_weibo_http('http://i.service.t.sina.com.cn/sapps/live/getlivelist.php', $data) )) {
            return s_err_sdk();
        }

        //缓存
        s_memcache($key, $data);
    }

    return $data;
}

function s_weibo_list_ago($list) {
    if (s_bad_array($list)) {
        return false;
    }

    foreach ($list as &$item) {
        if (isset($item['time'])) {
            $item['ago'] = s_weibo_ago($item['time']);
        }

        unset($item['fdate']);
        unset($item['ftime']);
        unset($item['status']);

        unset($item);
    }

    return $list;
}


function s_weibo_list_time($list, $format="m月d日 H:i", $postfix="") {
    if (s_bad_array($list)
        || s_bad_string($format)
    ) {
        return false;
    }

    foreach ($list as &$item) {
        if (isset($item['time'])) {
            $item['time'] = date($format . $postfix, $item['time']);
        }

        unset($item['fdate']);
        unset($item['ftime']);
        unset($item['status']);

        unset($item);
    }

    return $list;
}


function s_weibo_ago($time) {
    if (s_bad_id($time)) {
        $time = s_action_time();
    }

    $second = s_action_time() - $time;

	if (( $diff = intval($second / (60 * 60 * 24) )) > 0 ) {
        return $diff . "天前";

    } else if (( $diff = intval($second / (60 *60) )) > 0) {
        return $diff . "小时前";

    } else if (( $diff = intval($second / 60) ) > 0) {
        return $diff . "分钟前";
	}

	return "刚刚发表";
}

//返回以json格式的weibo数据，此处为做error_code检查
function s_live_http($url, $params=false, $method="get") {
    if (false === $params) {
        $params = array();
    }

    //添加COOKIE
    $params["cookie"]["SUE"] = $_COOKIE["SUE"];
    $params["cookie"]["SUP"] = $_COOKIE["SUP"];

    //添加APPKEY
    $params["source"] = APP_KEY;

    //上传图片。有两种情况
    //  1、@/image/web.jpg
    //  2、图片数据
    //
    if (isset($params["pic"])
        && is_string($params["pic"])
    ) {
        //检查数据是二进制文件还是路径
        $params["_name"] = "pic";

        if ( substr($params["pic"], 0, 1) === '@' ) {
            //@是路径
            $params["_data"] = file_get_contents(substr($params["pic"], 1));

        } else {
            //直接使用
            $params["_data"] = $params["pic"];
        }


        unset($params["pic"]);
    }

    //上传头像
    if (isset($params["image"])
        && is_string($params["image"])
    ) {
        //检查数据是二进制文件还是路径
        $params["_name"] = "image";

        if ( substr($params["image"], 0, 1) === '@' ) {
            //@是路径
            $params["_data"] = file_get_contents(substr($params["image"], 1));

        } else {
            //是图片数据
            $params["_data"] = $params["image"];
        }

        unset($params["image"]);
    }


    if (false === ( $data = s_http_json($url, $params, $method) )
        || isset($data['error_code'])
    ) {
        var_dump($data);
        return false;
    }

    return $data;
}


//返回用户前20条微博列表
function s_weibo_list_by_uid($uid, $page=1, $count=20) {
    if (s_bad_id($uid)
        || s_bad_id($page)
        || s_bad_id($count)

        || $count > 200
    ) {
        return false;
    }

    //看cache中是否存在
    $key = "weibo_list_by_uid#" . $uid . $page. $count;

    if (false === ( $data = s_memcache($key) )) {
        //缓存中没有，请求服务器
        $params = array(
            "user_id"   => $uid,
            "count"     => $count,
            "page"      => $page,
        );

        if (false === ( $data = s_weibo_http('http://api.t.sina.com.cn/statuses/user_timeline.json', $params) )) {
            return false;
        }
       
        //缓存起来900秒（15分钟）
        //$mem->set($key, $data, 0, 900);
    }

    return $data;
}

