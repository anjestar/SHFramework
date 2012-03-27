<?php
/*********************************************************
 * common file
 * 
 * Jory <jorygong@gmail.com>
 * 2006-4-19
**********************************************************/

require_once('smarty.php');
require_once('DB.php');
require_once('weibooauth.php');
require_once('memcache.php');
require_once("HTTP/Request.php");

//URL:  http://all.vic.sina.com.cn/TransparencyTour
//AKEY: 2565690238
//SKEY: 52814e0c070879c5fdbf88abf02ee87e

define("IMG_CACHE_DIR",$_SERVER['SINASRV_CACHE_DIR']);
define("IMG_CACHE_URL",$_SERVER['SINASRV_CACHE_URL']);
define("MBLOG_APP_KEY", 2565690238);
define("MBLOG_APP_SKEY", "52814e0c070879c5fdbf88abf02ee87e");
define("OAUTH_USER", "0a8574cfa1980a79cfe62579379c1117");
define("OAUTH_PASS", "12e4291cb67bc4fced05016b6ba28262");

define("MBLOG_WEIBO_ID", 1787844107);
define("MBLOG_APP_NAME", "TransparencyTour");
define("MBLOG_DB_NAME", "201203" . MBLOG_APP_NAME);
define("MBLOG_APP_URL", "http://all.vic.sina.com.cn/TransparencyTour");


function check_phone($phone) {
    if(preg_match("/^1[3,5,8][0-9]{1}[0-9]{8}$/",$phone)){    
        return true;    
    } else {    
        return false;
    }   
}

function show_pic($p,$w,$h) {
	$pr = array();
	if ($w < $h) {
		$pr["size"] = "height";

	} else {

		$pr["size"] = "width";
	}

	if ($p) {
		$pr["src"] = IMG_URL.$p;
		$pr["s_src"] = IMG_URL.get_ico($p);

	} else {
		$pr["src"] = IMG_NO;
		$pr["s_src"] = IMG_NO;
	}

	return $pr;
}
/*

function weibo_public_list_by_id($id, $page=1, $count=50) {
    if (( $id = intval($id) ) <= 0) {
        return false;
    }


}


//获取微博列表（包含个人和公共微博）,每页50条
function weibo_list_by_id($id, $page=1, $public=false) {
    if (( $id = intval($id) ) < 1) {
        return false;
    }

    //获取微博的接口
    $url = "";
    if ($public !== false) {
        //公共的微博
        $url = "http://api.t.sina.com.cn/statuses/public_timeline.json";
    } else {
        //个人微博
        $url = "https://api.weibo.com/2/statuses/user_timeline.json";
    }


    //使用的是内部接口
    $req =& new HTTP_Request($url);
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addQueryString('source', MBLOG_APP_KEY);
	$req->addQueryString('page', $page);
	$req->addQueryString($key, $token);
    if (PEAR::isError($req->sendRequest())
        || false === ( $data = $req->getResponseBody() )
        || false === ( $data = json_decode($data, true) )
        || isset($data["error"])
        || isset($data["error_code"])
        || !isset($data["id"])
    ) {
        return false;
	}

    return $data;


}
 */


//Email验证
function is_email($email)
{
	if ( preg_match("/^([_.0-9a-z-]+)@([0-9a-z][0-9a-z-]+[\.])+([a-z]{2,4})$/i",$email) ) return true;
	else return false;
}

/**
 * URL地址合法性检测
 * @return bool
 */
function is_url($value)
{
	if (preg_match("/^http:\/\/\w+.\w+.\w+/", $value) == 1)
	{
		return true;
	}
	return false;
}//end funtion


//小图片
function get_ico($filename, $prefix = "s_")
{
    return dirname($filename) . "/s_" . basename($filename);
}

//获取用户IP
function get_ip()
{
	if (isset($_SERVER))
	{
		if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
		{
			$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		else if (isset($_SERVER["HTTP_CLIENT_IP"]))
		{
			$realip = $_SERVER["HTTP_CLIENT_IP"];
		}
		else
		{
			$realip = $_SERVER["REMOTE_ADDR"];
		}
	}
	else
	{
		if (getenv("HTTP_X_FORWARDED_FOR"))
		{
			$realip = getenv("HTTP_X_FORWARDED_FOR");
		}
		else if (getenv("HTTP_CLIENT_IP"))
		{
			$realip = getenv("HTTP_CLIENT_IP");
		}
		else
		{
			$realip = getenv("REMOTE_ADDR");
		}
	}
	return $realip;
}//end funtion


//显示IP，最后一位置为*
function show_ip($ip)
{
	if ($ip)
	{
		$ip = substr($ip,0,strrpos($ip,".")).".*";
	}
    else
    {
    	$ip = "*";
    }
    return $ip;
}


//PHP 正则表达式验证中文字符(包括繁体中文)、英文、数字、减号及下划线。
function check_accounts($str, $minlen=0,$maxlen=0)
{
	if(preg_match("/^[\xA1-\xFEa-z0-9_-]*$/i",$str))
	{
        if ($minlen && (strlen($str) < $minlen))
        {
        	return false;
        }
        if ($maxlen && (strlen($str) > $maxlen))
        {
        	return false;
        }
		return true;
	}
	else
	{
		return false;
	}
}

//密码字符检查
//且长度不小于 $len
function check_pwd($str, $len=0)
{
	if(preg_match("/^([0-9a-z_-])*$/i",$minlen=0,$maxlen=0))
	{
        if ($minlen && (strlen($str) < $minlen))
        {
        	return false;
        }
        if ($maxlen && (strlen($str) > $maxlen))
        {
        	return false;
        }
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * 产生一个随机字符串
 *
 * @param $length:随机数长度
 *
 * @return string
 */
function random($length) {
	$hash = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
	$max = strlen($chars) - 1;
	mt_srand((double)microtime() * 1000000);
	for($i = 0; $i < $length; $i++) {
		$hash .= $chars[mt_rand(0, $max)];
	}
	return $hash;
}//end funtion



//截取定长字符串,
//参数$len为字符个数，一个汉字计算两个
function short_str($str,$len=120)
{ 
	$str_len=strlen($str); 

	for($i=0;$i<$str_len;$i++) { 
		if ($i>=$len) break;
		$ord_code = ord(substr($str,$i,1));
		if($ord_code>0xa0) { 
			$tmpstr.=substr($str,$i,2); 
			$i++; 
		} else { 
			$tmpstr.=substr($str,$i,1); 
		} 
	} 	

	return $tmpstr; 
}


//根据VID获取视频缩略图
/**
 * 根据视频ID获取视频的封面
 *
*/
class VideoImages
{
 function VideoImages()
 {
  return true;
 }
 
 function show($vid, $order = 1)
 {
  $order = intval($order);
  if ($order < 0) 
  { 
   $order = 0;
  }
  if ($order > 2) 
  {
   $order = 2;
  }
  $vid = intval($vid);
  if ($vid <= 0) 
  {
   return "";
  }
  $basepath = VideoImages::_get_new_imags_path($vid);
  return $basepath.$vid.'_'.$order.'.jpg';
 }
 
 function _get_new_imags_path($vid)
 {
  $imd5 = md5($vid);
  $apath = VideoImages::_twHash(substr($imd5, 0, 16), 1024);
  $bpath = VideoImages::_twHash(substr($imd5, 16), 1024);
  return "http://p.v.iask.com/".$apath."/".$bpath."/";
 }
 
 function _get_old_imags_path($vid)
 {
  $key = intval($vid);
  $pidone = $key % 10;
  $pid = $key % 100;
  return "http://image2.sina.com.cn/kusou/v/".$pidone."/".$pid."/".$pid."/";
 }
 
 function _twHash($str , $size)
 {
  $b = array(0 , 0 , 0 , 0);
  for($i=0 ; $i<strlen($str) ; $i++)
  {
   $b[$i%4] ^= ord($str[$i]);
  }
  for($i=0 ; $i<4 ; $i++)
  {
   $tempbin = decbin($b[3-$i]);
   $temp0 = "";
   for($j=0 ; $j<8-strlen($tempbin) ; $j++)
   {
    $temp0 .= "0";
   }
   $tempbin = $temp0.$tempbin;
   $binstr .= $tempbin;
  }
  $n = bindec($binstr);
  return $n%$size;
 }
}

function show_video_pic($vid)
{
	if ($vid) {
		$ps = VideoImages::show($vid,1);

	} else {
		$ps = IMG_DEFAULT;
	}
	return $ps;
}

//登陆新浪通行证信息
function user_cookie_auth()
{
	require_once("SSOCookie.class.php");
	$cookie = new SSOCookie("cookie.conf");//配置文件cookie.conf的路径
	$userinfo = array();

	if (false === $cookie->getCookie($userinfo)) {
        return false;
    }

	return $userinfo;
}

//获取用户信息
function get_user($uid) {
    if (( $uid = intval($uid) ) <= 0) {
        return false;
    }
    
	$key = md5(MEM_CACHE_KEY_PREFIX . "_user_" . $uid);
	$mem = mem_cache_share();

    //TODO：可以修改
    if (!( $user = $mem->get($key) )
        || !( $user = unserialize($user) )
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

            $mem->set($key, serialize($user), 0, 3600);
        }
    }

	return $user;
}


//获取用户信息，可以是ID也可以是screen_name
//如果指定$auto = false，只有$token为数字才进行ID查找
//没有返回false
function get_user_by_token($token, $auto=true) {
    $key = null;

    if ($auto === true) {
        //取消自动判断
        $key = intval($token) == $token ? "uid" : "screen_name";

    } else if (is_int($token)) {
        //是数字
        $key = "uid";

    } else if (is_string($token)) {
        //是字符
        $key = "screen_name";
    }

    if ($key === null) {
        return false;
    }

    //使用的是内部接口
    $req =& new HTTP_Request('http://i2.api.weibo.com/2/users/show.json');    				
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addQueryString('source', MBLOG_APP_KEY);
	$req->addQueryString($key, $token);
    if (PEAR::isError($req->sendRequest())
        || false === ( $data = $req->getResponseBody() )
        || false === ( $data = json_decode($data, true) )
        || isset($data["error"])
        || isset($data["error_code"])
        || !isset($data["id"])
    ) {
        return false;
	}

    return $data;
}


//通过个性域名获取用户信息
//$domain: 只需要为hiduan即可，不需要http://weibo.com/hiduan
function get_user_by_domain($domain) {
    if (!is_string($domain)) {
        return false;
    }

    //使用的是内部接口
    $req =& new HTTP_Request('http://i2.api.weibo.com/2/users/domain_show.json');
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addQueryString('source', MBLOG_APP_KEY);
	$req->addQueryString("domain", $domain);
    if (PEAR::isError($req->sendRequest())
        || false === ( $data = $req->getResponseBody() )
        || false === ( $data = json_decode($data, true) )
        || isset($data["error"])
        || isset($data["error_code"])
        || !isset($data["id"])
    ) {
        return false;
	}

    return $data;
}


function get_weibo_list_by_name($name, $page=1, $count=20) {
    if (false === ( $user = get_user_by_token($naem) )) {
        return false;
    }

    return get_weibo_list_by_uid($user["id"]);
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
