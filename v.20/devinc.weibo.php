<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.bad.php
//	判断错误的函数，参数错误返回true，正确返回false
//
//  s_weibo_list_by($wid)
//	    判断数字是否正确（大于0）
//  
//
//
////////////////////////////////////////////////////////////////////////////////



function s_weibo_by_wid($wid) {
    if (s_bad_id($wid)) {
        return s_err_arg();
    }


    $key = 'weibo_by_wid#' . $wid;

    if (false === ( $data = s_memcache($key) )) {
        $param = array(
            'id'    => $wid,
        );

        if (false === ( $data = s_weibo_http('https://api.weibo.com/2/statuses/show.json', $param) )) {
            //有些微博会被删除，所以不提示
            return false;
        }

        //缓存24小时
        s_memcache($key, $data, 24 * 3600);
    }

    return $data;
}


//对微博的数据简单的处理下
function s_weibo_sample(&$weibo, $timeshow='ago', $autolink=true) {
    if (s_bad_array($weibo)) {
        return false;
    }

    //添加wid变量和uid变量
    if (isset($weibo['idstr'])) {
        $weibo['wid']   = $weibo['idstr'];
    }

    if (isset($weibo['user']['idstr'])) {
        $weibo['uid']   = $weibo['user']['idstr'];
    }

    //添加缩略图 small 和 big变量
    if (isset($weibo['thumbnail_pic'])) {
        $weibo['small'] = $weibo['thumbnail_pic'];
    }

    if (isset($weibo['original_pic'])) {
        $weibo['big']   = $weibo['original_pic'];
    }


    //添加用户头像 a50 和 a180
    if (isset($weibo['user']['profile_image_url'])) {
        $weibo['a50']   = $weibo['user']['profile_image_url'];
    }

    if (isset($weibo['user']['avatar_large'])) {
        $weibo['a180']  = $weibo['user']['avatar_large'];
    }

    //添加用户的昵称
    if (isset($weibo['user']['profile_url'])) {
        $weibo['purl'] = $weibo['user']['profile_url'];
    }

    if (isset($weibo['user']['screen_name'])) {
        $weibo['uname'] = $weibo['user']['screen_name'];
    }

    if (isset($weibo['created_at'])) {
        //时间 ago / x月x日 x时x分
        if ($timeshow === 'ago') {
            // 换成'三天以前'
            $weibo['time'] = s_weibo_ago($weibo['created_at'], false);

        } else {
            // 换成timeshow的格式
            $weibo['time'] = s_weibo_time($weibo['created_at'], $timeshow);
        }
    }


    if ($autolink === true) {
        //将text中所有微博关键字都添加为可点击连接
        $weibo['text'] = s_string_at($weibo['text']);
        $weibo['text'] = s_string_turl($weibo['text']);
        $weibo['text'] = s_string_face($weibo['text']);
        $weibo['text'] = s_string_subject($weibo['text']);
    }

    return $weibo;
}



//对微博的数据简单的处理下
function s_weibo_sample_list(&$list, $timeshow='ago', $autolink=true) {
    if (s_bad_array($list)) {
        return false;
    }

    foreach ($list as &$item) {
        s_weibo_sample($item, $timeshow, $autolink);

        unset($item);
    }

    return $list;
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


//格式化微博平台的时间
//  将"Sun Jul 08 21:29:04 +0800 2012"格式化成"7月8日 21:29"
function s_weibo_time($time, $format="m月d日 H:i", $postfix="") {
    if (s_bad_string($time)
        || s_bad_string($format)
        || s_bad_0string($postfix)
    ) {
        return false;
    }

    return date($format . $postfix, strtotime($time));
}


//格式化微博平台的时间
//  将"Sun Jul 08 21:29:04 +0800 2012"格式化成"3天前"
function s_weibo_ago($time, $isint=true) {
    if ($isint !== true) {
        $time = strtotime($time);
    }

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


//返回以json格式的weibo数据，此处为做error_code检查
function s_weibo_http($url, &$params=false, $method='get', $mutil=false) {
    if (false === $params) {
        $params = array();
    }

    if (empty($params['access_token'])) {
        //采用系统指定的APP_KEY（dev/devinc.common.php指定）
        $params["source"] = isset($params['APP_KEY']) ? $params['APP_KEY'] : APP_KEY;
    }

    //获取本地cookie
    foreach($_COOKIE as $key => $value) {
        $cookies[] = $key . "=" . rawurlencode($value);
    }


    $header     = array();
    $header[]   = 'Cookie: '        . implode('; ', $cookies);
    $header[]   = 'Referer: '       . ( isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '' );
    $header[]   = 'User-Agent: '    . $_SERVER['HTTP_USER_AGENT'];


    //有一些错误码不需要返回false
    if (false === ( $response = s_http_response($url, $params, $method, $mutil, $header, false) )
        || false === ( $json = json_decode($response, true) )
    ) {
        s_action_error($json['error'], $json['error_code']);

        exit($json['error_code']);
    }

    return $json;
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
       
        //缓存起来60秒
        $mem->set($key, $data, 0, 60);
    }

    return $data;
}


//返回微博列表数据（内部接口）
//  wids        需要查找的微博主键，每次最多查询50个微博主键
//  user        0，返回用户详情; 1，只返回用户主键
//
function s_weibo_list_by_wids($wids, $user=0) {
    if (!is_array($wids)
        || count($wids) > 50
    ) {
        return false;
    }

    $wids = implode(',', $wids);

    //看cache中是否存在
    $key = "weibos_list_by_wids#wids=" . $wids . "user=" . $user;

    if (false === ( $ret = s_memcache($key) )) {
        //缓存中没有，请求服务器
        $params = array(
            "ids"       => $wids,
            "trim_user" => $user,
        );

        if (false === ( $ret = s_weibo_http('http://i2.api.weibo.com/2/statuses/show_batch.json', $params) )) {
            return false;
        }

        //缓存起来5分钟
        s_memcache($key, $ret, 300);
    }


    return $ret['statuses'];
}


//返回带gps信息的微博数据，默认每页20条
function s_weibo_gps_list_by_uid($uid, $page=1, $count=20) {
    if (s_bad_id($uid)
        || s_bad_id($page)
        || s_bad_id($count)

        || $count > 200
    ) {
        return false;
    }

    //看cache中是否存在
    $key = "weibo_gps_list_by_uid#" . $uid . $page. $count;

    if (false === ( $data = s_memcache($key) )) {
        //缓存中没有，请求服务器
        $params = array(
            "user_id"   => $uid,
            "count"     => $count,
            "page"      => $page,
        );

        if (false === ( $data = s_weibo_http('http://i2.api.weibo.com/2/place/user_timeline.json', $params) )) {
            return false;
        }

        //缓存起来60秒
        s_memcache($key, $data, 60);
    }

    return $data;
}


function s_weibo_2id_by_mids($mids, $type=1) {
    $is_array = 0;

    if (is_array($mids)) {
        rsort($mids, SORT_STRING);

        $mids = implode(',', $mids);

        $is_array = 1;
    }


    if (s_bad_string($mids)) {
        return false;
    }


    //看cache中是否存在
    $key = "weibo_2id_by_mids#mids=" . $mids . "type=" . $type;

    if (false === ( $data = s_memcache($key) )) {
        //缓存中没有，请求服务器
        $params = array(
            "mid"       => $mids,
            "type"      => $type,
            "is_batch"  => $is_array,
            "isBase62"  => 1,
        );

        if (false === ( $data = s_weibo_http('https://api.weibo.com/2/statuses/queryid.json', $params) )) {
            return false;
        }

        //缓存起来24小时
        s_memcache($key, $data, 24 * 3600);
    }

    if (!$is_array) {
        //非数组查询，只返回对应的id
        return $data['id'];
    }


    $ret = array();

    //数组需要处理下
    foreach ($data as &$item) {
        $ret = array_merge($ret, $item);

        unset($item);
    }

    unset($data);

    return $ret;
}


//返回微博的详细信息
//  $mid 可为简单数组 如 array('YQsdz13', 'YQsdz13', 'YQsdz13', 'YQsdz13', 'YQsdz13', 'YQsdz13')
//       可为联合数组 如 array(array('mid' => 'YQsdz12'), array('mid' => 'YQsdz12'))
//       可为mid字符  如 YQsdz13
//
function s_weibo_detail_by_mid($mid, $key=false) {
    if (is_string($mid)) {
        //查一个
        $mid = array($mid);

    } else if (is_array($mid)) {
        //查多个
        if (is_string($key)) {
            //是一个联合数组，那么按$key取值
            $list = $mid;

            $mid = array();

            foreach ($list as $item) {
                if (!s_bad_string($item[$key], $id)) {
                    $mid[] = $id;
                }
            }

            unset($list);
        }
    }

    if (s_bad_array($mid)
        //得到所有的id
        || false == ( $data = s_weibo_2id_by_mids($mid) )
    ) {
        return false;
    }

    //查询所有的微博详情
    $list = array();

    foreach ($data as $key=>$wid) {
        $list[$key] = s_weibo_by_wid($wid);
    }


    return $list;
}


//微博关注列表微博主键
function s_weibo_forward_ids($wid, $since_id=0, $max_id=0) {
    if (s_bad_id($wid)
        || s_bad_0id($max_id)
        || s_bad_0id($since_id)
    ) {
        return false;
    }

    $page   = 1;        //当前页
    $size   = 50;       //每页数
    $ret    = array();

    while ($page > 0) {
        //看cache中是否存在
        $mkey = "weibo_forward_ids#"
            . 'wid='    . $wid
            . 'page='   . $page
            . 'size='   . $size
            . 'max='    . $max_id
            . 'since='  . $since_id;

        if (false === ( $data = s_memcache($mkey) )) {
            //缓存中没有，请求服务器
            $params = array(
                'id'        => $wid,
                'page'      => $page,
                'count'     => $size,
                'max_id'    => $max_id,
                'since_id'  => $since_id,
            );

            if (( $data = s_weibo_http('https://api.weibo.com/2/statuses/repost_timeline/ids.json', $params) )
                && isset($data['statuses'])
                && count($data['statuses'])
            ) {
                //缓存起来60秒
                s_memcache($mkey, $data, 60);
            }
        }

        if (isset($data['statuses'])
            && count($data['statuses'])
        ) {
            //计算总页数
            $ret = array_merge($ret, $data['statuses']);
        }

        $page = intval($data['next_cursor']);
    }

    //返回整个微博转发列表的微博主键
    return $ret;
}



//推送通知（高级接口）
//      如果turl为false，会转换成http://t.cn/SADAxdda短链。为true时不转成短链
//
//  uids = array(uid1, uid2, uid3, uid4);
//  $tid = 1232ad3121;
//  keys = array(
//      'object1'   => 啊段的马甲,
//      'object2'   => 通过,
//  );
//  $url = "http://duanyong.tk/index.php";
//
function s_weibo_notice(&$uids, $tid, $keys=false, $url=false, $noticeid=false) {
    if (s_bad_array($uids)
        || s_bad_string($tid)
    ) {
        return false;
    }


    $_keys = false;
    $_uids = implode(',', $uids);

    if ($keys) {
        $_keys = array_values($keys);
        $_keys = implode('&', $keys);
    }


        $data = array(
            'uids'      => $_uids,
            'tpl_id'    => $tid,
        );

        if ($url) {
            //如果是有url添加
            $data['action_url'] = $url;
        }

        if ($keys) {
            //合并模板数据
            $data = array_merge($data, $keys);
        }

        if ($noticeid) {
            //通知需要用新的APP_KEY
            $data['_APP_KEY'] = $noticeid;
        }


        if (false === ( $data = s_weibo_http('http://i2.api.weibo.com/2/notification/send.json', $data, 'post') )) {
            return s_err_sdk();
        }


    return $data;
}


//将url转换成短链
function s_weibo_surl($url) {
    if (s_bad_string($url)) {
        return false;
    }

    //看cache中是否存在
    $mkey = 'weibo_surl#surl=' . $url;
    if (false === ( $data = s_memcache($mkey) )) {
        //缓存中没有，请求服务器
        $params = array(
            'url_long'  => $url,
        );

        if (false === ( $data = s_weibo_http('https://api.weibo.com/2/short_url/shorten.json', $params) )) {
            return false;
        }

        //缓存起来一天
        s_memcache($mkey, $data, 86400);
    }

    return $data;
}

