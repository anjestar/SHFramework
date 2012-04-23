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
function s_user_by_uid($uid) {
    if (s_bad_id($uid)) {
        return false;
    }

	$key = "user_by_uid#" . $uid;
    $arr = array(
        "uid"   => $uid,
    );

    if (false === ( $user = s_memcache($key) )
        || false === ( $user = s_weibo_http("", $arr) )
    ) {
        return false;
    }

	return $user;
}



//按screen_name获取用户信息
function s_user_by_nickname($nickname) {
    if (s_bad_string($nickname)) {
        return false;
    }

    $key = "user_by_nickname#" . $nickname;

    if (false === ( $uid = s_memcache_get($key) )) {
        //缓存中不存在，从API获取uid缓存起来
        $params = array(
            "screen_name"   => $nickname,
        );

        //缓存1小时
        //$mem->set($key, $uid, 0, 3600);
    }

    return s_user_data_by_uid($uid);
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


//批量获取用户信息
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

            //echo "list:", var_dump($list);
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
function s_user_post_weibo($uid, $weibo) {
    if (s_bad_id($uid)
        || ( $mem = s_memcache_global() ) === false
    ) {
        return false;
    }

    if (s_bad_id($page)) {
        //默认第一页
        $page = 1;
    }

    if (s_bad_id($count)) {
        //默认20条
        $count = 20;
    }

    if ($count > 200) {
        //超过200不能获取
        return false;
    }


    //看cache中是否存在
	$key = md5(MEM_CACHE_KEY_PREFIX . "_weibo_list_" . $uid . $page. $count);

    if (false === ( $data = $mem->get($key) )) {
        //缓存中不存在，从API获取微博列表缓存起来
        $list = s_http_request();
    }

    if (!$data) {
        //缓存中没有，请求服务器
        //参考：http://open.weibo.com/wiki/2/statuses/user_timeline
        $req = new HTTP_Request('/statuses/user_timeline.json'); 
        $req->setMethod(HTTP_REQUEST_METHOD_GET);
        $req->addQueryString('user_id', $uid);	
        $req->addQueryString('source', MBLOG_APP_KEY); 
        $req->addQueryString('count',$count); 
        $req->addQueryString('page', $page); 
        $rs = $req->sendRequest();

        if (PEAR::isError($rs)
            || false === ( $data = $req->getResponseBody() )
            || false === ( $data = json_decode($data, true) )
            || isset($data["error"])
            || isset($data["error_code"])
        ) {
            return false;
        }
       
        //缓存起来900秒（15分钟）
		$mem->set($key, json_encode($data), 0, 900);
    }

    return $data;
}

function s_send_to_my_wblog($content) {
	$req = new HTTP_Request('http://api.t.sina.com.cn/statuses/update.json');    			
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));	
	$req->addPostData('status', URLEncode($content));	
	$req->addPostData('source',MBLOG_APP_KEY);
	$rs = $req->sendRequest();
    if (PEAR::isError($rs)
        || false === ( $data = json_decode($req->getResponseBody(), true) )
        || isset($data["erro"])
    ) {
        return false;
	}

    return $data;
}

function s_repost_twblog($content, $mid) {
	$req = new HTTP_Request('https://api.weibo.com/2/statuses/repost.json');    				
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));	
	//$req->addPostData('status', URLEncode($content));
	$req->addPostData('status', $content);	
	$req->addPostData('source',MBLOG_APP_KEY);
	$req->addPostData('id',$mid);
	$rs = $req->sendRequest();
	if (!PEAR::isError($rs))
	{
		$data= $req->getResponseBody();
	}
	return json_decode($data, true);
}
