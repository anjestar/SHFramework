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


//返回以json格式的weibo数据，此处为做error_code检查
function s_weibo_http($url, &$params=false, $method="get") {
    if (false === $params) {
        $params = array();
    }

    //添加COOKIE
    $params["cookie"]["SUE"] = $_COOKIE["SUE"];
    $params["cookie"]["SUP"] = $_COOKIE["SUP"];

    //添加APPKEY
    $params["source"] = APP_KEY;


    $boundarys = array();

    if (isset($params["avatar"])) {
        //上传头像
        //检查数据是二进制文件还是路径
        if (is_string($params["avatar"])) {
            $boundarys["image"] = '@' . $params["avatar"];

        } else {
            //二进制文件
            $boundarys["image"] = &$params["avatar"];
        }

        unset($params["avator"]);
    }


    if (isset($params["image"])) {
        //上传图片
        //检查数据是二进制文件还是路径
        if (is_string($params["image"])) {
            $boundarys["image"] = '@' . $params["image"];

        } else {
            //二进制文件
            $boundarys["pic"] = &$params["image"];
        }

        unset($params["image"]);
    }

    if (isset($params["file"])) {
        //一般文件
        foreach ($params["file"] as &$value) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params["file"]);

            unset($value);
        }

        unset($params["file"]);
    }

    if (false === ( $data = s_http_json($url, $params, $method) )
        || isset($data["error_code"])
    ) {
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
