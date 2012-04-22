<?php
////////////////////////////////////////////////////////////////////////////////
//
// devinc.user.php
//	用户操作的函数
//
//	s_user_logined()
//	    返回用户是否登录的状态
//  
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


//检查用户是否登录
function s_user_is_logined() {
    return s_sso_auth() !== false;
}


//获取用户的信息（先从缓存中获取，再从API中获取）
function s_user_data_by_uid($uid=false) {
    if (s_bad_id($uid)
        || ( $mem = s_memcache_global() ) === false
    ) {
        return false;
    }

	$key = md5(MEM_CACHE_KEY_PREFIX . "_user_" . $uid);

    if (!( $user = $mem->get($key) )
        || !( $user = json_decode($user, true) )
    ) {
        $weibo  = new WeiboClient(MBLOG_APP_KEY, MBLOG_APP_SKEY, OAUTH_USER, OAUTH_PASS);
        $info   = $weibo->show_user($uid);

        if($info['id']) {
            $user['id']                 = $info['id'];
            $user['attnum']             = $info['followers_count'] ? $info['followers_count']: 0; //粉丝数
            $user['domain']             = $info['domain'] ? $info['domain'] : "";
            $user['location']           = $info['location'] ? $info['location'] : "";
            $user['attention']          = $info['friends_count'] ? $info['friends_count'] : 0; //关注数
            $user['mblogCount']         = $info['statuses_count'] ? $info['statuses_count'] : 0; //发微博数
            $user['screen_name']        = $info['screen_name'] ? $info['screen_name'] : "";
            $user['profile_image_url']  = $info['profile_image_url'] ? $info['profile_image_url'] : "";
            $user['favourites_count']   = $info['favourites_count'] ? $info['favourites_count'] : 0; //收藏数

            //缓存1小时
            $mem->set($key, json_encode($user), 0, 3600);
        }
    }

	return $user;
}



//按screen_name获取用户信息
function s_user_by_nickname($nickname) {
    if (s_bad_string($nickname)
        || ( $mem = s_memcache_global() ) === false
    ) {
        return false;
    }

    $key = md5(MEM_CACHE_KEY_PREFIX . "_nickname_" . $nickname);

    if (false === ( $uid = $mem->get($key) )) {
        //缓存中不存在，从API获取uid缓存起来
        if ( false === ( $user = s_http_request("/users/show.json", array("screen_name" => $nickname)) )
            || s_bad_id($user["id"], $uid)
        ) {
            return false;
        }

        //缓存1小时
        $mem->set($key, $uid, 0, 3600);
    }

    return s_user_data_by_uid($uid);
}


//通过个性域名获取用户信息
//$domain: 只需要为hiduan即可，不需要http://weibo.com/hiduan
function s_user_by_domain($domain) {
    if (s_bad_string($domain)
        || ( $mem = s_memcache_global() ) === false
    ) {
        return false;
    }

    $key = md5(MEM_CACHE_KEY_PREFIX . "_domain_" . $domain);

    if (false === ( $uid = $mem->get($key) )) {
        //缓存中不存在，从API获取uid缓存起来
        if ( false === ( $user = s_http_request("/users/domain_show.json", array("domain" => $domain)) )
            || s_bad_id($user["id"], $uid)
        ) {
            return false;
        }

        //缓存1小时
        $mem->set($key, $uid, 0, 3600);
    }

    return s_user_by_uid($uid);
}


//获取用户的微博列表，超过200条返回false
function s_weibo_list_by_uid($uid, $page=1, $count=20) {
    if (s_bad_id($uid)
        || s_bad_id($page)
        || s_bad_id($count)

        || $count > 200
    ) {
        return false;
    }


    //看cache中是否存在
    $key = "weibo_list_by_uid_page_count#{$uid}_{$page}_{$count}";

    if (false === ( $data = s_memcache($key) )) {
        $params = array(
            "user_id"   => $uid,
            "count"     => $count,
            "page"      => $page,
        );

        if (false === ( $data = s_weibo_http("http://api.t.sina.com.cn/statuses/update.json", $params) ) {
            return false;
        }

        //存储到缓存中
    }

    return $data;
}


//用户发微博
function s_user_post_weibo($weibo) {
    if (is_string($weibo)) {
        $weibo = array(
            "status" => $weibo,
        );
    }

    if (s_bad_string($weibo["status"], $text)) {
        return false;
    }


    $weibo["status"] = urlencode($text);

    return s_weibo_http('http://api.t.sina.com.cn/statuses/update.json', $weibo, 'post');
}


//用户回复微博
function s_user_repost_weibo($weibo) {
    if (s_bad_id($weibo["id"])
        || s_bad_string($weibo["status"])
    ) {
        return false;
    }

    return s_weibo_http('https://api.weibo.com/2/statuses/repost.json', $weibo, 'post');
}


//用户转发微博
function s_user_forward_weibo($weibo) {
    if (s_bad_id($weibo["id"])
        || s_bad_string($weibo["comment"])
    ) {
        return false;
    }

    return s_weibo_http('https://api.weibo.com/2/comments/create.json', $weibo, 'post');
}

//用户好友搜索
function s_user_search($name, $count=10, $page=1) {
    if (s_bad_string($name)
        || s_bad_id($page)
        || s_bad_id($count)
    ) {
        return false;
    }

    $params = array(
        'q'     => $name,
        'page'  => $page,
        'count' => $count,
    );

    //区分精确搜索还是模糊搜索
	return s_weibo_http('http://api.t.sina.com.cn/users/search.json', $params);    
}


function s_check_user() {
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
function s_user_firends_ids_by_uid($uid, $page=1, $count=50) {
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
