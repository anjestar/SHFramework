<?PHP
/*********************************************************
 * 动态平台 memcache 
 * 新申请的PHP5空间环境变量不同，请使用本函数
 * 
 **********************************************************/

define("MEM_CACHE_KEY_PREFIX", $_SERVER["SINASRV_MEMCACHED_KEY_PREFIX"]); //定义memcache的key前缀
define("MEM_CACHE_LIFETIME",60); //缓存时间,单位秒,0表示长久有效
define("MEM_CACHE_LIFETIME_SMALL",120); //缓存时间,单位秒,0表示长久有效
define("MEM_CACHE_LIFETIME_LONG",120); //缓存时间,单位秒,0表示长久有效
define("MEM_CACHE_LIFETIME_LUCKY",36000); //缓存时间十小时
define("MEM_CACHE_LIFETIME_LUCKY_CHECK",1); //缓存时间十小时

//使用本组IDC的memcache
function &mem_cache_local()
{
	$mc = new Memcache;
	$servers = explode(" ",$_SERVER["SINASRV_MEMCACHED_SERVERS"]);
	foreach($servers as $val)
	{
		$v = explode(":",$val);
		$mc->addServer($v[0],$v[1]);
	}
	return $mc;
}

//使用共享的memche
function &mem_cache_share()
{
	$mc = new Memcache;
	$servers = explode(" ",$_SERVER["SINASRV_GLOBAL_MEMCACHED_SERVERS"]);
	foreach($servers as $val)
	{
		$v = explode(":",$val);
		$mc->addServer($v[0],$v[1]);
	}
	return $mc;
}
?>
