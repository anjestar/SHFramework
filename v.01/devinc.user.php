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

            $req = &new HTTP_Request('http://i2.api.weibo.com/2/users/show_batch.json');
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
        $req =& new HTTP_Request('/statuses/user_timeline.json'); 
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


function get_weibo_list_by_uid($uid, $page=1, $count=20) {
    if (( $uid = intval($uid) ) <= 0) {
        return false;
    }

    if (( $page = intval($page) ) <= 0) {
        //默认第一页
        $page = 1;
    }

    if (( $count = intval($count) ) <= 0) {
        //默认20条
        $count = 20;

    } else if ($count > 200) {
        return false;
    }


    //看cache中是否存在
	$mem = mem_cache_share();
	$key = md5(MEM_CACHE_KEY_PREFIX."_weibo_list_" . $uid . $page. $count);

    if (( $data = $mem->get($key) )) {
        //缓存中已经存在
		$data = json_decode($data, true);
    }

    if (!$data) {
        //缓存中没有，请求服务器
        $req =& new HTTP_Request('http://api.t.sina.com.cn/statuses/user_timeline.json'); 
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

function send_to_my_wblog($content) {
	$req =& new HTTP_Request('http://api.t.sina.com.cn/statuses/update.json');    			
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

function repost_twblog($content, $mid) {
	$req =& new HTTP_Request('https://api.weibo.com/2/statuses/repost.json');    				
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

function comments_twblog($content, $mid) {
	$req =& new HTTP_Request('https://api.weibo.com/2/comments/create.json');    				
	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));	
	//$req->addPostData('comment', URLEncode($content));	
	$req->addPostData('comment', $content);	
	$req->addPostData('source',MBLOG_APP_KEY);
	$req->addPostData('id',$mid);
	$rs = $req->sendRequest();
	if (!PEAR::isError($rs))
	{
		$data= $req->getResponseBody();
	}
	return json_decode($data, true);
}


//允许20以内的长url换算成t.cn
function weibo_turl($urls) {
    if (is_string($urls)) {
        $urls = array($urls);
    }

    if (!is_array($urls)
        || empty($urls)
        || count($urls) > 20
    ) {
        return false;
    }

    $req =& new HTTP_Request('http://api.t.sina.com.cn/short_url/shorten.json');    				
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addCookie("SUE", URLEncode($_COOKIE["SUE"]));
	$req->addCookie("SUP", URLEncode($_COOKIE["SUP"]));
	$req->addQueryString('source', MBLOG_APP_KEY);

    foreach ($urls as $item) {
        $req->addQueryString('url_long', $item);
    }


	if (PEAR::isError($req->sendRequest())
        || false === ( $data = $req->getResponseBody() )
        || false === ( $data = json_decode($data, true) )
        || isset($data["error"])
        || isset($data["error_code"])
    ) {
        return false;
	}

    return count($data) === 1 && isset($data[0]["url_short"]) ? $data[0]["url_short"] : $data;
}


function check_user()
{
	$req =& new HTTP_Request('http://api.t.sina.com.cn/account/verify_credentials.json');    
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));		
	$req->addQueryString('source',MBLOG_APP_KEY);  
	$rs = $req->sendRequest();
	if (!PEAR::isError($rs))
	{
		$data= $req->getResponseBody();
	}
	return json_decode($data);
}

function user_search($name, $count=10, $page=1) {
	$req =& new HTTP_Request('http://api.t.sina.com.cn/users/search.json');    
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addQueryString('q', $name);
	$req->addQueryString('page', $page);
	$req->addQueryString('count', $count);
	$req->addQueryString('source', MBLOG_APP_KEY);  
	$rs = $req->sendRequest();

	if (PEAR::isError($rs)) {
        return false;
    }

    if (false === ( $data = json_decode($req->getResponseBody(), true) )
        || isset($data["error"])
        || isset($data["error_code"])
    ) {
        return false;
    }

    return $data;
}



function get_fans($json,$arr) {
	$req =& new HTTP_Request('http://api.t.sina.com.cn/statuses/followers.json');    
	$req->setMethod(HTTP_REQUEST_METHOD_GET);	
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));	
	$req->addQueryString('cursor', $arr['cursor']);
	$req->addQueryString('count', $arr['count']);
	//$req->addQueryString('id', $arr['id']);
	$req->addQueryString('source',MBLOG_APP_KEY);  	
	$rs = $req->sendRequest();

	if (!PEAR::isError($rs)) {
		$data= $req->getResponseBody();
	}

	return $json->decode($data);
}


//批量获取用户信息
function get_users_by_uids(&$uids, $encoded=false) {
    if (!is_array($uids)
        || !( $uids = array_unique($uids) )
        || !( $uids = array_values($uids) )
        || empty($uids)
    ) {
        return false;
    }

    //看cache中是否存在
    asort($uids);
	$mem  = mem_cache_share();
    $key  = md5(MEM_CACHE_KEY_PREFIX."_user_details_" . implode(",", $uids) . strval($encoded));

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


            $req =& new HTTP_Request('http://i2.api.weibo.com/2/users/show_batch.json');    
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


//返回互粉列表，只返回ID数组，并没有用户数据
function get_user_firends_ids_by_uid($uid, $page=1, $count=50) {
    if (( $uid = intval($uid) ) <= 0) {
        return false;
    }

    if (( $page = intval($page) ) <= 0) {
        $page = 1;
    }

    if (( $count = intval($count) ) <= 0) {
        $count = 50;
    }

    if ($count > 200) {
        return false;
    }

    //先看缓存中是否存在
	$mem = mem_cache_share();
	$key = md5(MEM_CACHE_KEY_PREFIX . "_firends_" . $uid . $page. $count);

    if (( $data = $mem->get($key) )) {
        //缓存中已经存在
		$data = json_decode($data, true);
    }

    if (!$data) {
        $req =& new HTTP_Request('https://api.weibo.com/2/friendships/friends/bilateral/ids.json');    
        $req->setMethod(HTTP_REQUEST_METHOD_GET);	
        $req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));
        $req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));
        $req->addQueryString('uid', $uid);
        $req->addQueryString('sort', 0);
        $req->addQueryString('page', $page);
        $req->addQueryString('count', $count);
        $req->addQueryString('source', MBLOG_APP_KEY);


        if (PEAR::isError( $rs = $req->sendRequest() )
            || !( $data = json_decode($req->getResponseBody(), true) )
            || isset($data["error_code"])
            || !isset($data["ids"])
        ) {
            return false;
        }

        $data = $data["ids"];

        //缓存600秒
        $mem->set($key, json_encode($data), 0, 600);
    }

    return $data;
}



//围脖请求
function curl_url($url, $type, $postdata = "")
{
	$req = new HTTP_Request($url);
	if ($type == 'get') {
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
	}
	else if ($type == 'post') {
		$req->setMethod(HTTP_REQUEST_METHOD_POST);
	}
	if ($postdata) {
		foreach ($postdata as $k1=>$v1) {
			$req->addPostData($k1,$v1);
		}
		foreach ($_COOKIE as $k=>$v)
		{
			$req->addCookie($k,urlencode($v));
		}
	}
	$response = $req->sendRequest();
	if (PEAR::isError($response)) {
	  return $response->getMessage();
	   //echo '发送请求错误';
		//	exit();
	} else {
	   return $req->getResponseBody();	   
	    //exit();
	}
}

//转化时间和现在相差的秒和分钟
function B_time($t) {
	$t = iconv('gbk','utf-8',$t);
	$t = strtotime($t);
	$second=time()-$t;
	$d=intval($second/(60*60*24));
	$h=intval($second/(60*60));
	$m=intval($second/60);
	$time = "";
	if($d>0) {
		$time = $d."天前";
	}
	else if($h>0){
		$time = $h."小时前";
	}
	else if($m>0) {
		$time = $m."分钟前";
	}
	else {
		$time = "刚刚发表";
	}
	return $time;
}

//转化时间和现在相差的秒和分钟
function B_time2($t)
{
		$t = iconv('gbk','utf-8',$t);
		$t = strtotime($t);
		$second=time()-$t;
		$d=intval($second/(60*60*24));
		$h=intval($second/(60*60));
		$m=intval($second/60);
		$time = "";
		if($d>0) {
			$time = $d."天前";
		}
		else if($h>0){
			$time = $h."小时前";
		}
		else if($m>0) {
			$time = $m."分钟前";
		}
		else {
			$time = "刚刚发表";
		}
		return $time;
	 //return iconv('gbk','utf-8',$time);
}

//微博请求
function post_t($url, $param) {
    if (false === ( $req =& new HTTP_Request($url) )) {
        return false;
    }

	$req->setMethod(HTTP_REQUEST_METHOD_POST);
	foreach ($param as $k1=>$v1) {
		//$req->addPostData($k1,urlencode(trim(iconv("gb2312","utf-8",$v1))));
		$req->addPostData($k1,$v1);
	}

	foreach ($_COOKIE as $k=>$v)
	{
		$req->addCookie($k,urlencode($v));
	}		
	$response = $req->sendRequest();
	if (PEAR::isError($response)) {
	  return $response->getMessage();
	   //echo '发送请求错误';
		//	exit();
	} else {
	   return $req->getResponseBody();
	    //exit();
	}
}

function alert_msg_uft8($err, $msg) {
	$ret =  iconv('gb2312','utf-8',$msg);
	echo json_encode(array ('error'=>$err, 'msg'=>$ret));
	exit(0) ;
}

//查看两个用户之间是否为好友（互粉）关系
function get_user_is_friendship($uida, $uidb) {
    if (( $uida = intval($uida) ) <= 0
        || ( $uidb = intval($uidb) ) <=0
        || $uida == $uidb
    ) {
        return false;
    }

    //先看缓存中有没有关系，因为两次HTTP请求，太浪费时间了
	$key = md5(MEM_CACHE_KEY_PREFIX . "_friended_" . (( $uida > $uidb ) ? $uida . $uidb : $uidb . $uida));
	$mem = mem_cache_share();

    if (!( $data = $mem->get($key) )) {
        //得到用户的信息

        //查看用户a是否关注用户b
        $req =& new HTTP_Request("http://api.t.sina.com.cn/friendships/exists.json");
        $req->setMethod(HTTP_REQUEST_METHOD_GET);
        $req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));
        $req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));
        $req->addQueryString('user_a', $uida);
        $req->addQueryString('user_b', $uidb);
        $req->addQueryString('source', MBLOG_APP_KEY); 
        $rs = $req->sendRequest();

        if (PEAR::isError($rs)
            || false === ( $shipa = json_decode($req->getResponseBody(), true) )
            || isset($shipa["error"])
            || $shipa["friends"] === false
        ) {
            return false;
        }

        if ($shipa["friends"] === true) {
            //查看用户b是否关注用户a
            $req =& new HTTP_Request("http://api.t.sina.com.cn/friendships/exists.json");
            $req->setMethod(HTTP_REQUEST_METHOD_GET);
            $req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));
            $req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));
            $req->addQueryString('user_b', $uida);	
            $req->addQueryString('user_a', $uidb);	
            $req->addQueryString('source', MBLOG_APP_KEY); 
            $rs = $req->sendRequest();

            if (PEAR::isError($rs)
                || false === ( $shipb = json_decode($req->getResponseBody(), true) )
                || isset($shipb["error"])
            ) {
                return false;
            }

            unset($rs);
            unset($req);
        }

        $data = $shipa["friends"] === true && $shipb["friends"] === true ? "1" : "0";

        //缓存600秒
        $mem->set($key, $data, 0, 600);
    }


    return $data == "1";
}


function is_friend($user1, $user2) {
	$post_data['source']=MBLOG_APP_KEY;
	$post_data['user_a']=$user1;
	$post_data['user_b']=$user2;
	$url="http://api.t.sina.com.cn/friendships/exists.json";
	$r=post_t($url, $post_data);
	$r=json_decode($r, true);

    return $r["friends"] == 1;
}

function add_friend($uid) {
	$post_data['source']=MBLOG_APP_KEY;
	$url="http://api.t.sina.com.cn/friendships/create/".$uid.".json";
	$r = post_t($url, $post_data);

	return json_decode($r, true);
}

function get_lasted_wb($id, $count) {
	//得到用户的信息
	$req =& new HTTP_Request('http://api.t.sina.com.cn/statuses/user_timeline.json'); 
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	//$req->setBasicAuth('david_thyme', '850526'); 
	$req->addQueryString('id', $id);	
	$req->addQueryString('source',MBLOG_APP_KEY); 
	$req->addQueryString('count',$count); 
	$req->addQueryString('page',1); 
	$rs = $req->sendRequest();
	if (!PEAR::isError($rs))
	{
		$data= $req->getResponseBody();
	}
	$data=json_decode($data);	
	return $data;
}

function iconv_str($str) {
	return iconv('utf-8','gbk',$str);
}

function iconv_str_utf8($str) {
	return iconv('gbk', 'utf-8',$str);
}


function add_weibo_file($status, $file) {
	//$status=iconv("gbk","utf-8",$status);
	$boundary = uniqid('------------------');
	$MPboundary = '--'.$boundary;
	$endMPboundary = $MPboundary. '--';
	$multipartbody .= $MPboundary . "\r\n";
	$multipartbody .= 'Content-Disposition: form-data; name="pic"; filename="wiki.jpg"'. "\r\n";
	$multipartbody .= 'Content-Type: image/jpg'. "\r\n\r\n";
	$multipartbody .= $file. "\r\n";
	
	$k = "source";
	$v = MBLOG_APP_KEY;
	$multipartbody .= $MPboundary . "\r\n";
	$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
	$multipartbody.=$v."\r\n";
	
	$k = "status";
	$v = $status;
	$multipartbody .= $MPboundary . "\r\n";
	$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
	$multipartbody.=$v."\r\n";
	$multipartbody .= "\r\n". $endMPboundary;
	
	$ch = curl_init();
	$cookie=join_cookie();
	
	curl_setopt($ch, CURLOPT_COOKIE , $cookie);
	curl_setopt( $ch , CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt( $ch , CURLOPT_POST, 1 );
	curl_setopt( $ch , CURLOPT_POSTFIELDS , $multipartbody );
	$url = 'http://api.t.sina.com.cn/statuses/upload.json';
	$header_array = array("Content-Type: multipart/form-data; boundary=$boundary" , "Expect: ");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array );
    curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_HEADER , true );
	curl_setopt($ch, CURLINFO_HEADER_OUT , true );
	$info = curl_exec( $ch );
	curl_close($ch);
	$temp=explode('{"created_at":',$info);

	return json_decode('{"created_at":'.$temp[1], true);
}

//修改头像
function update_profile_image($file) {
	$boundary = uniqid('------------------');
	$MPboundary = '--'.$boundary;
	$endMPboundary = $MPboundary. '--';

	$multipartbody .= $MPboundary . "\r\n";
	$multipartbody .= 'Content-Disposition: form-data; name="image"; filename="wiki.jpg"'. "\r\n";
	$multipartbody .= 'Content-Type: image/jpg'. "\r\n\r\n";
	$multipartbody .= $file. "\r\n";

	$k = "source";
	// 这里改成 appkey
	$v = MBLOG_APP_KEY;
	$multipartbody .= $MPboundary . "\r\n";
	$multipartbody.='content-disposition: form-data; name="'.$k."\"\r\n\r\n";
	$multipartbody.=$v."\r\n";
	$multipartbody.= "\r\n". $endMPboundary;

	$ch = curl_init();
	$cookie=join_cookie();
	curl_setopt($ch, CURLOPT_COOKIE , $cookie);
	curl_setopt( $ch , CURLOPT_RETURNTRANSFER, true);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt( $ch , CURLOPT_POST, 1 );
	curl_setopt( $ch , CURLOPT_POSTFIELDS , $multipartbody );
	//$url = 'http://api.t.sina.com.cn/account/update_profile_image.json' ;
	$url = 'http://i2.api.weibo.com/2/account/avatar/upload.json';
	$header_array = array("Content-Type: multipart/form-data; boundary=$boundary" , "Expect: ");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header_array );
    curl_setopt($ch, CURLOPT_URL, $url );
	curl_setopt($ch, CURLOPT_HEADER , true );
	curl_setopt($ch, CURLINFO_HEADER_OUT , true );
	$info = curl_exec( $ch );
	curl_close($ch);
	$temp=explode('{"id":',$info);
	//return json_decode('{"id":'.$temp[1].'{"id":'.$temp[2],true);
	return json_decode('{"id":'.$temp[1], true);
}

function combinPic($pic1,$pic2) {
	 if($pic1=='' || $pic2=='')
        return '';
        $img1  = "/data2/www/userupload/".time().rand(10000,99999)."_1.jpg";
        $img2  = "/data2/www/userupload/".time().rand(10000,99999)."_2.jpg";
        @file_put_contents($img1,file_get_contents($pic1));
        $im=imagecreatefromjpeg($img1);
        $im_pic2=imagecreatefrompng($pic2);
        imagecopyresized($im,$im_pic2,69,130,0,0,106,45,106,45);
        //unlink($img1);
        imagejpeg($im,$img2,100);
        return $img2;
}
	
function join_cookie(){
    foreach( $_COOKIE as $k=>$v )
    {
		$d[] =$k."=".urlencode($v);
    }
	$data = implode("; ",$d);
	return $data;
}

function getmblogid_new($base62) {
	if($base62!='') {
		$rs=@file_get_contents("http://api.t.sina.com.cn/queryid.json?mid=$base62&isBase62=1&type=1");  //$rs=json_decode($rs);{"id":"1041391054"}
		$rs=json_decode($rs,true);
		return $rs['id'];
	}
	else{
		return '';
	}
} 


function get_attentions($sinaid, $count){
	$url="http://api.t.sina.com.cn/statuses/followers.json";
	$url  .= "?source=".MBLOG_APP_KEY."&user_id=".$sinaid."&count=".$count;
	$req =& new HTTP_Request($url);    
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addCookie("SUE",URLEncode($_COOKIE["SUE"]));                     
	$req->addCookie("SUP",URLEncode($_COOKIE["SUP"]));	 	
	$rs = $req->sendRequest();
	if (!PEAR::isError($rs))
	{
		$data= $req->getResponseBody();
	}
	$result  = json_decode($data,true);
	$friends  = array();
	$len  = count($result);
	for($i =0;$i < $len; $i++) {
		$friends[$i]['sinaid']  = $result[$i]['id'];
		$friends[$i]['screen_name']  = iconv_str($result[$i]['screen_name']);
		$friends[$i]['profile_image_url']  =$result[$i]['profile_image_url'];
	}
	return $friends;
}
