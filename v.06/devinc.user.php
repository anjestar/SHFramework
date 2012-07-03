<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.user.php
//	用户操作的函数
//
//  s_user_by_uid($uid)
//      根据uid返回用户的基本信息
//
//
//
//  依赖：
//      devinc.bad.php
//      devinc.sso.php
//      devinc.memcache.php
//
//
////////////////////////////////////////////////////////////////////////////////


//获取用户的信息（先从缓存中获取，再从API中获取）
function s_user_by_uid($uid, $sample=true) {
    if (s_bad_id($uid)) {
        return false;
    }

	$key = "user_by_uid#" . $uid;

    if (false === ( $ret = s_memcache($key) )) {
        if (false === ( $ret = s_weibo_http("https://api.weibo.com/2/users/show.json", array('uid' => $uid)) )) {
            return s_err_sdk();
        }

        //由于不包括经常更换的数据，所以存储时间为1天
        s_memcache($key, $ret, 24 * 3600);
    }

    //规范标准输出
    $ret['uid']        = $ret['id'];
    $ret['uname']      = $ret['screen_name'];
    $ret['a50']        = $ret['profile_image_url'];
    $ret['a180']       = $ret['avatar_large'];
    $ret['purl']       = $ret['profile_url'];

    unset($ret['avatar_large']);
    unset($ret['profile_image_url']);

    if ($sample === true) {
        //删除一些多余的数据
        unset($ret['status']);
    }

	return $ret;
}



//按screen_name获取用户信息
function s_user_by_nickname($nickname) {
    if (s_bad_string($nickname)) {
        return false;
    }

    $key = "uid_by_nickname#" . $nickname;

    if (false === ( $uid = s_memcache($key) )) {
        //缓存中不存在，从API获取uid缓存起来
        $arr = array(
            "screen_name"   => $nickname,
        );

        if (false === ( $ret = s_weibo_http("https://api.weibo.com/2/users/show.json", $arr) )
            || s_bad_id($ret['id'], $uid)
        ) {
            return false;
        }

        //缓存1小时
        s_memcache($key, $uid);
    }

    return s_user_by_uid($uid);
}


//通过个性域名获取用户信息（http://weibo.com/hiduan: $domain => "hiduan"）
function s_user_by_domain($domain) {
    if (s_bad_string($domain, $domain)) {
        return false;
    }

    $key    = "user_by_domain_" . $domain;
    $params = array("domain" => $domain);

    if (false === ( $data = s_memcache_get($key) )
        || false === ( $user = s_weibo_http("/users/domain_show.json", $params) )
    ) {
        //缓存中不存在
        return false;
    }


    //获取uid，从缓存中获取用户信息
    return s_user_by_uid($uid);
}


//批量获取用户信息（内部接口，外部禁用）
function s_users_by_uids(&$uids, $encoded=false) {
    if (!s_bad_array($uids)
        || !( $uids = array_unique($uids) )
        || !( $uids = array_values($uids) )
        || empty($uids)
    ) {
        return false;
    }

    //看cache中是否存在
    asort($uids);
	$mem  = mem_cache_share();
    $key  = md5(MEM_CACHE_KEY_PREFIX."_user_by_uids_" . implode(",", $uids) . strval($encoded));

    if (( $data = $mem->get($key) )) {
        //缓存中已经存在
		$data = json_decode($data, true);
    }

    if (!$data) {
        //缓存中没有，请求服务器
        $max    = 20;
        $time   = 0;
        $times  = ceil(count($uids) / $max);
        $list   = array();

        do {
            $ids = array();

            $num0 = $time * $max;
            $num1 = ($time + 1) * $max - 1;

            foreach (range($num0, $num1) as $index) {
                if (!isset($uids[$index])
                    || intval($uids[$index]) <= 0
                ) {
                    break;
                }

                $ids[] = $uids[$index];
            }

            $params = array(
                "uids"   => implode(",", $ids),
                "source" => APP_KEY,
                "cookie" => array(
                    "SUE"   => $_COOKIE["SUE"],
                    "SUP"   => $_COOKIE["SUP"],
                ),
            );

            $data = s_http_get();

            $req = new HTTP_Request('http://i2.api.weibo.com/2/users/show_batch.json');
            $req->setMethod(HTTP_REQUEST_METHOD_GET);	
            $req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));
            $req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));
            $req->addQueryString('uids', implode(",", $ids));
            $req->addQueryString('is_encoded', $encoded === false ? 0 : 1);
            $req->addQueryString('source', MBLOG_APP_KEY);

            $rs = $req->sendRequest();


            if (PEAR::isError($rs)
                || !( $ret = json_decode($req->getResponseBody(), true) )
                || isset($ret["error_code"])
            ) {
                return false;
            }

            //有可能是空数组
            if (isset($ret["users"])) {
                $list = array_merge($list, $ret["users"]);
            }

            unset($ret);
        } while (( ++ $time ) < $times);


        $data = array();

        //重新组合成uid => array()
        foreach ($list as &$item) {
            if (isset($item["id"])
                && $item["idstr"] > 0
            ) {
                $data[$item["idstr"]] = $item;
            }

            unset($item);
        }


        //检查自己是否在数组中
        if (false !== ( $me = login_user_info() )
            && ( $meid = $me["uniqueid"] )
            && in_array($meid, $uids)
            && ( $me = get_user_by_token(intval($meid)) )
        ) {
            $data[$me["id"]] = $me;
        }

        unset($list);
       
        //缓存十小时
		$mem->set($key, json_encode($data), 0, MEM_CACHE_LIFETIME_LUCKY);
    }

    return $data;
}


//用户发布徽博
//  $weibo['pic'] 有三种情况：
//      1、@/var/www/project/images/wb.jpg
//          不做任何改变
//      2、@./image/web.jpg
//          路径被转换成绝对路径
//      3、由flash上传的图片数据
//
function s_user_post(&$weibo) {
    if (is_string($weibo)) {
        $weibo = array(
            "status" => $weibo,
        );
    }

    if (s_bad_array($weibo)
        || s_bad_string($weibo["status"])
    ) {
        return false;
    }


    //对图片是否绝对路径
    if (!s_bad_string($weibo['pic'], $path)) {
        //以@./相对路径开头
        if (0 === strpos($path, '@./')) {
            $weibo['pic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . '/' . APP_NAME . substr($path, 2);

        } else if (0 === strpos($path, './')) {
            $weibo['pic'] = '@' . $_SERVER['DOCUMENT_ROOT'] . '/' . APP_NAME . substr($path, 1);
        }
    }

    if (isset($weibo["pic"])) {
        //发图片微博
        $url = "http://upload.api.weibo.com/2/statuses/upload.json";

    } else {
        //发文字微博
        $url = "https://api.weibo.com/2/statuses/update.json";

    }

    return s_weibo_http($url, $weibo, "post");
}


//用户回复微博
function s_user_reply($weibo) {
    if (s_bad_array($weibo)
        || s_bad_id($weibo["id"])
        || s_bad_string($weibo["comment"])
    ) {
        return false;
    }

    return s_weibo_http("https://api.weibo.com/2/comments/create.json", $weibo);
}


//用户回复评论
function s_user_reply_comment($weibo) {
    if (s_bad_array($weibo)
        || s_bad_id($weibo["id"])
        || s_bad_string($weibo["comment"])
    ) {
        return false;
    }

    return s_weibo_http("https://api.weibo.com/2/comments/reply.json", $weibo);
}

//用户更新头像（高级权限）
function s_user_avatar(&$avatar) {
    if (s_bad_string($avatar)) {
        return false;
    }

    $data = array();

    //以@./相对路径开头
    if (0 === strpos($avatar, '@./')) {
        $data['image'] = $_SERVER['DOCUMENT_ROOT'] . '/' . APP_NAME . substr($avatar, 2);

    } else if (0 === strpos($avatar, './')) {
        $data['image'] = '@' . $_SERVER['DOCUMENT_ROOT'] . '/' . APP_NAME . substr($avatar, 1);

    } else {
        $data['image'] = $avatar;
    }

    return s_weibo_http('https://api.weibo.com/2/account/avatar/upload.json', $data, 'post');
    //    http://i2.api.weibo.com/2/account/avatar/upload.json
}


//用户关注某人
function s_user_follow($fuid) {
    $data = array();

    if (!s_bad_id($fuid)) {
        //微博ID
        $data['uid'] = $fuid;

    } else if (!s_bad_string($fuid)) {
        //微博昵称
        $data['screen_name'] = $fuid;
    }

    if (s_bad_array($data)) {
        return s_err_arg();
    }

    //2.0接口返回程序未被授权
    //return s_weibo_http("https://api.weibo.com/2/friendships/create.json", $data, "post");
    return s_weibo_http("http://api.t.sina.com.cn/friendships/create/{$fuid}.json", $data, "post");
}


//用户的粉丝列表
function s_user_followers($uid, $count=200, $page=1) {
    if (s_bad_id($count)
        || s_bad_id($page)
    ) {
        return s_err_arg();
    }

    if (!s_bad_id($uid)) {
        //微博ID
        $data['uid'] = $uid;

    } else if (!s_bad_string($uid)) {
        //微博昵称
        $data['screen_name'] = $uid;
    }

    $data['count']  = $count > 5000 ? 200 : $count;
    //游标从0开始
    $data['cursor'] = $page - 1;

    $key = "user_followers_by_uid#{$uid}_{$count}_{$page}";

    if (false !== ( $users = s_memcache($key) )) {
        return $users;
    }

    //缓存中没有，从微博平台中获取
    if ( false === ( $ret = s_weibo_http("https://api.weibo.com/2/friendships/followers.json", $data) )
        || s_bad_array($ret['users'])
    ) {
        return false;
    }


    $users = s_user_sample($ret['users']);

    //缓存中存储起来
    s_memcache($key, $users);

    return $users;
}


//用户的互粉列表
function s_user_friends($uid, $count=200, $page=1) {
    if (s_bad_id($count)
        || s_bad_id($page)
    ) {
        return s_err_arg();
    }

    if (!s_bad_id($uid)) {
        //微博ID
        $data['uid'] = $uid;

    } else if (!s_bad_string($uid)) {
        //微博昵称
        $data['screen_name'] = $uid;
    }

    $data['count']  = $count > 5000 ? 200 : $count;
    //游标从0开始
    $data['cursor'] = $page - 1;

    $key = "user_followers_by_uid#{$uid}_{$count}_{$page}";

    if (false !== ( $users = s_memcache($key) )) {
        return $users;
    }

    //缓存中没有，从微博平台中获取
    if ( false === ( $ret = s_weibo_http("https://api.weibo.com/2/friendships/followers.json", $data) )
        || s_bad_array($ret['users'])
    ) {
        return false;
    }


    $users = s_user_sample($ret['users']);

    //缓存中存储起来
    s_memcache($key, $users);

    return $users;
}

//用户与对方之间的关系
function s_user_ship($uid) {
    $data = array();

    if (!s_bad_id($uid)) {
        //微博ID
        $data['target_id'] = $uid;

    } else if (!s_bad_string($uid)) {
        //微博昵称
        $data['target_screen_name'] = $uid;
    }

    if (s_bad_array($data)) {
        return s_err_arg();
    }

    //2.0接口返回程序未被授权
    //return s_weibo_http("https://api.weibo.com/2/friendships/create.json", $data, "post");
    return s_weibo_http("http://api.t.sina.com.cn/friendships/show.json", $data);
}


function s_user_sample(&$users) {
    if (s_bad_array($users)) {
        return false;
    }

    foreach ($users as &$user) {
        $user['id']         = $user['id'];
        $user['name']       = $user['screen_name'];
        $user['purl']       = $user['profile_image_url'];
        $user['wurl']       = $user['profile_url'];

        unset($user);
    }

    return $users;
}

