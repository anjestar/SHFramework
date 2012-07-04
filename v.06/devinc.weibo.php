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
        if (false === ( $data = s_weibo_http('https://api.weibo.com/2/statuses/show.json', array('id'=>$wid)) )) {
            //有些微博会被删除，所以不提示
            return false;
        }

        //缓存24小时
        s_memcache($key, $data, 24 * 3600);
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
function s_weibo_http($url, $params=false, $method="get") {
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
        return s_err_action($data['error']);
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
       
        //缓存起来60秒
        $mem->set($key, $data, 0, 60);
    }

    return $data;
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
        $mem->set($key, $data, 0, 60);
    }

    return $data;
}


function s_weibo_2id_by_mids($mids, $type=1) {
    $is_array = 0;

    if (is_array($mids)) {
        sort($mids, SORT_STRING);

        $mids = implode(',', $mids);

        $is_array = 1;
    }


    if (s_bad_string($mids)) {
        return false;
    }


    //看cache中是否存在
    $key = "weibo_2id_by_mids#" . $mids;

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


        $mid = array_unique($mid);
    }

    if (s_bad_array($mid)
        //得到所有的id
        || false == ( $mid = s_weibo_2id_by_mids($mid) )
    ) {
        return false;
    }

    //查询所有的微博详情
    $list = array();

    foreach ($mid as $key=>$wid) {
        $list[$key] = s_weibo_by_wid($wid);
    }


    return $list;
}


//搜索微博数据（内部接口）
function s_weibo_search($sid, $uid=false, $q=false, $page=1, $size=10, $istag=0, $sort='time', $start=false, $end=false) {
    if (s_bad_string($sid)) {
        return s_err_arg();
    }


    //看cache中是否存在
    $key = "weibo_search#" . $uid . $q . $page . $size . $istag . $sort . $start . $end . $sid;

    if (false === ( $data = s_memcache($key) )) {
        //缓存中没有，请求服务器
        $params = array(
            'sid'       => $sid,
            'page'      => $page,
            'count'     => $size,
        );

        if (is_string($q)) {
            $params['q'] = $q;
        }

        if (!s_bad_0id($istag)) {
            $params['istag'] = $istag;
        }

        if (!s_bad_id($uid)) {
            $params['uid'] = $uid;
        }

        if (!s_bad_id($start)) {
            $params['starttime'] = $start;
        }

        if (!s_bad_id($end)) {
            $params['endtime'] = $end;
        }

        if (false === ( $data = s_weibo_http('http://i2.api.weibo.com/2/search/statuses.json', $params) )) {
            return false;
        }

        //缓存起来60秒
        s_memcache($key, $data, 60);
    }

    return $data;
}
