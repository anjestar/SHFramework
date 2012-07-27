<?php

//define("SHF_DIR", $_SERVER["DOCUMENT_ROOT"] . '/SHFramework');

require_once("devinc.common.php");

//$_COOKIE['SUP'] = 'cv=1&bt=1338360118&et=1338446518&d=40c3&i=cdc8&us=1&vf=0&vt=0&ac=2&lt=1&uid=2680839281&user=hiduan%40qq.com&ag=4&name=hiduan%40qq.com&nick=%E5%95%8A%E6%AE%B5%E7%9A%84%E9%A9%AC%E7%94%B2&sex=1&ps=0&email=&dob=&ln=hiduan%40qq.com&os=&fmp=&lcp=';
/*
$_COOKIE = array(
    //"SINAGLOBAL"        => "00000063.90e1539d.4f9a65f2.2292066a",
        //"U_TRS1"        => "00000063.2d21ee4.4fa0cae3.4c322160",
        //"__utma"        => "264698335.167411114.1336115368.1338348918.1338353478.39",
        //"__utmz"        => "264698335.1338187672.33.6.utmcsr=e.weibo.com|utmccn=(referral)|utmcmd=referral|utmcct=/2735133692/app_4244285305",
        //"__v"           => "1.1195762501452536000.1336376237.1337335675.1337574930.5",
        //"__c_review"    => "1",
        //"__c_last"      => "1337311164644",
        //"__c_visitor"   => "1336463514556165",
        //"UOR"           => "all.vic.sina.com.cn,auto.sina.com.cn,",
        //"vjuids"        => "-5b42b90a0.1372f9e8db4.0.e2747fe72b40d8",
        //"vjlast"        => "1338275886",
        //"ULV"           => "1338256419389:4:4:2:000000c2.b8f8489a.4fc32a3a.6403a57c:1338183448389",
        //"__c_uactiveat" => "1337047786946",
        //"ALLYESID4"     => "0012051809371737531932",
        //"SINA_NEWS_CUSTOMIZE_city"
                        //=> "%u5317%u4EAC",
        //"SGUP"          => "0",
        //"U_TRS2"        => "00000047.9ff466fd.4fc3297d.fb85b8f2",
        //"Apache"        => "000000c2.b8f8489a.4fc32a3a.6403a57c",
        //"USRMDE2"       => "usrmdinst79_8089",
        //"BIGipServerpool_dpool2_web2"
                        //=> "437663242.20480.0000",
        //"usrmd"         => "usrmdins680",
        //"_s_upa"        => "6",
        //"PHPSESSID"     => "1ju8msgr88pcmilnncngagh5b4",
        //"ULOGIN_IMG"    => "fe55e24c15322ed8e655ce949078e67299d6",
        //"__utmc"        => "264698335",
        //"USRMDE1"       => "usrmdinst6113",
        //"SH_DYNAMIC2"   => "587412490.20480.0000",
        //"USRMDE18"      => "usrmd81",
        //"SUS"           => "SID-2680839281-1338360118-XD-g87kx-562f0c8002355f2e8a9889e70472cdc8",
        "SUE"           =>  "es=dffd3e79408f9ebbaf6ed823c49b2ed5&ev=v1&es2=cd10115111908746ca9e9ae31d45361c&rs0=rOnoAIKHelU9y6LVyG0iz3%2BuRjNGdVSlPNitcJx1a0JkUnUwIXPsE1P6HYhs1J1Zv%2FLCIqYlhwWEC5EZ1C5OyKX9F1f%2FYklcEtRi9S5wTj2EQTzs5t26mCBieIOn5d4bPaJ6PNZUXKJzO3baTJsVogezxauKsttow4yp9QXIKJs%3D&rv=0",
        "SUP"           => "cv=1&bt=1338360118&et=1338446518&d=40c3&i=cdc8&us=1&vf=0&vt=0&ac=2&lt=1&uid=2680839281&user=hiduan%40qq.com&ag=4&name=hiduan%40qq.com&nick=%E5%95%8A%E6%AE%B5%E7%9A%84%E9%A9%AC%E7%94%B2&sex=1&ps=0&email=&dob=&ln=hiduan%40qq.com&os=&fmp=&lcp=",
        //"ALF"           => "1338964918",
        //"SUR"           => "uid=2680839281&user=hiduan%40qq.com&nick=%E5%95%8A%E6%AE%B5%E7%9A%84%E9%A9%AC%E7%94%B2&email=&dob=&ag=4&sex=1&ssl=0",
);
 */


#测试KEY ，企业应用测试，一定要用hiduan@qq.com微博账号登陆
#
define("APP_KEY", 3388150372);
define("APP_SECRET", "99e78ca1a195ccc77b89a85e930a9de4");


define("OAUTH_USER", "8383ab8bfc755de4f981d3a7f833cc44");
define("OAUTH_PASS", "eabddf373d1bbbd13b2a416275a0c058");

define("APP_NAME", "shframework");
define("APP_WEIBOID", 2680839281);
define("APP_DB_PREFIX", "201205shf");
#define("APP_URL", "http://all.vic.sina.com.cn/disney");
#define("APP_TURL", "http://all.vic.sina.com.cn/disney");


if (s_bad_get('type', $type) ) {
    $type = 'getWeiboID';
}

if ($type == 'getUserDetail') {
    //查询用户详情
    if (s_bad_post('token', $token) ) {
        return s_action_error('require params: name or uid.');
    }

    if (!s_bad_id($token)) {
        //是数字，当UID
        $data = s_user_by_uid($token);

    } else if (!s_bad_string($token)) {
        echo 'null';
        //是字符，当username
        $data = s_user_by_nickname($token);
    }

} else if ($type === 'getWeiboDetail') {
    if (s_bad_post('token', $token) ) {
        return s_action_error('require params: wid.');
    }

    if (!s_bad_id($token)) {
        //是数字，当WID
        $data = s_weibo_by_wid($token);

    } else if (!s_bad_string($token)) {
        //是BASE64，当mid
        $data = s_weibo_by_mid($token);
    }
}

for ($i=0; $i < 10; ++ $i) {
    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);

    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);

    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);

    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);

    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);

    $weibo = "测试p/s, from ab, at:" . time() . " " . rand(1, 100000);
    s_user_post($weibo);
}



echo s_action_json(array(
    "error" => 0,
    //"data"  => s_user_post($weibo),
));
