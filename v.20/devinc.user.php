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

function s_user_sample(&$user) {
    if (s_bad_array($user)) {
        return false;
    }

    if (isset($user['idstr'])) {
        $user['uid'] = $user['idstr'];
    }

    if (isset($user['screen_name'])) {
        $user['uname'] = $user['screen_name'];
    }

    if (isset($user['profile_image_url'])) {
        $user['a50'] = $user['profile_image_url'];
    }

    if (isset($user['avatar_large'])) {
        $user['a180'] = $user['avatar_large'];
    }

    if (isset($user['profile_url'])) {
        $user['purl'] = $user['profile_url'];
    }

    return $user;
}

//获取用户的信息（先从缓存中获取，再从API中获取）
function s_user_by_uid($uid, $sample=true) {
    if (s_bad_id($uid)) {
        return false;
    }

	$key    = "user_by_uid#" . $uid;

    if (false === ( $ret = s_memcache($key) )) {
        $param  = array('uid' => $uid);

        if (false === ( $ret = s_weibo_http("https://api.weibo.com/2/users/show.json", $param) )) {
            return s_err_sdk();
        }

        //由于不包括经常更换的数据，所以存储时间为1天
        s_memcache($key, $ret, 24 * 3600);
    }
    var_dump($ret);
    exit();

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
    if (s_bad_array($uids)
        || !( $uids = array_unique($uids) )
        || !( $uids = array_values($uids) )
        || false === asort($uids)
    ) {
        return false;
    }


    $uids1  = implode(',', $uids);
    $key    = "users_by_uids#" . $uids1 . $encoded;

    //看cache中是否存在
    if (false === ( $data = s_memcache($key) )) {
        //从服务器获取
        $params = array();
        $params['uids']         = $uids1;
        //$params['trim_status '] = 1;
        //$params['simplify']     = 1;

        if (false === ( $data = s_weibo_http('http://i2.api.weibo.com/2/users/show_batch.json', $params) )) {
            return false;
        }

        //缓存24小时
        s_memcache($key, $data, 24 * 3600);
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
    if (s_bad_array($weibo)
        || s_bad_string($weibo["status"])
    ) {
        return false;
    }

    if (!$weibo['pic'] 
        || !$weibo['image']
    ) {
        unset($weibo['pic']);
        unset($weibo['image']);

        $url = 'https://api.weibo.com/2/statuses/update.json';

    } else {
        $url = 'http://upload.api.weibo.com/2/statuses/upload.json';
    }

    return s_weibo_http($url, $weibo, 'post', isset($weibo['pic']) || isset($weibo['image']));
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


//用户转发微博
//  $wid        微博主键
//  $message    回复内容
//  $reply      转发的形式：1、评论当前微博；2、评论原微博；3、1和2都操作
function s_user_forward(&$wid, &$message=false, $reply=0) {
    if (s_bad_id($wid)
        || s_bad_0id($reply)
    ) {
        return false;
    }

    $data['id']         = $wid;
    $data['is_comment'] = $reply;

    if ($message !== false) {
        $data['status'] = $message;
    }

    return s_weibo_http('https://api.weibo.com/2/statuses/repost.json', $data, 'post');
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
}


//用户关注某人
function s_user_follow($suid, $tuid) {
    if (s_bad_id($suid)
        || s_bad_id($tuid)
    ) {
        return false;
    }

    //2.0接口返回程序未被授权
    return s_weibo_http("https://api.weibo.com/2/friendships/create.json", array('uid' => $suid), "post");
}


//用户的粉丝列表
//  $sort   api     : 标准api
//          time    : 联系时间排序
//          follows : 粉丝数
//          hot     : 粉丝活跃度
//
function s_user_followers_by($uid, $page=1, $size=20, $sort='api') {
    if (s_bad_id($uid)
        || s_bad_id($page)
        || s_bad_id($size)
        || s_bad_string($sort)
    ) {
        return false;
    }

    if ($size > 200) {
        $size = 200;
    }

    $key = "user_follower_by#uid={$uid}&sort={$sort}&size={$size}&page={$page}";

    if (false === ( $ret = s_memcache($key) )) {
        //不同的排列方式对应不同的接口地址
        $urls = array(
            'api'       => 'https://api.weibo.com/2/friendships/followers.json',
            //按联系时间排序
            'time'      => 'http://i2.api.weibo.com/2/friendships/followers/sort_interactive.json',
            //按粉丝数排序
            'follows'   => 'http://i2.api.weibo.com/2/friendships/followers/sort_followers.json',
            //按粉丝活跃度排序
            'hot'       => 'http://api.t.sina.com.cn/friendships/followers/active.json',
        );

        if (s_bad_string($urls[$sort], $url)) {
            return false;
        }


        $data = array();
        $data['uid']    = $uid;
        $data['page']   = $page;
        $data['count']  = $size;

        if ( false === ( $ret = s_weibo_http($url, $data) )) {
            return false;
        }

        //缓存中存储起来（缓存5分钟）
        s_memcache($key, $ret, 300);
    }

    //返回处理之后的用户数据
    return $ret;
}


//用户的互粉列表
function _s_user_friends($uid, $count=200, $page=1) {
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


//用户的关注的列表
function s_user_attention($uid, $sort=0, $page=1, $size=20) {
    if (s_bad_id($uid)
        || s_bad_id($size)
        || s_bad_id($page)
        || s_bad_0id($sort)
    ) {
        return false;
    }

    $data = array();
    $data['uid']    = $uid;
    $data['page']   = $page;
    $data['count']  = $size;
    $data['sort']   = $sort;

    $key = "user_attentions_by_uid#{$uid}_{$type}_{$sort}_{$page}_{$size}";

    if (false === ( $ret = s_memcache($key) )) {
        if ( false === ( $ret = s_weibo_http("https://api.weibo.com/2/friendships/friends.json", $data) )) {
            return false;
        }

        //缓存中存储起来
        s_memcache($key, $ret);

    }
    return $ret;
}


//用户的双向关注的列表
function s_user_friends($uid, $page=1, $size=20, $sort='api') {
    if (s_bad_id($uid)
        || s_bad_id($page)
        || s_bad_id($size)
        || s_bad_string($sort)
    ) {
        return false;
    }

    $data = array();
    $data['uid']    = $uid;
    $data['page']   = $page;
    $data['count']  = $size;
    $data['sort']   = $sort;

    $key = "user_friends_by_uid#{$uid}_{$type}_{$sort}_{$page}_{$size}";

    if (false === ( $ret = s_memcache($key) )) {
        $urls = array(
            //按关注时间
            'api'   => "https://api.weibo.com/2/friendships/friends/bilateral.json",
            //按活跃度
            'hot'   => "https://api.weibo.com/2/friendships/friends.json",
        );

        if (s_bad_string($urls[$sort], $url)) {
            return false;
        }

        if ($sort === 'api') {
            $sort = 0;
        }

        if (false === ( $ret = s_weibo_http($url, $data) )) {
            return false;
        }

        //缓存中存储起来10分钟
        s_memcache($key, $ret, 60 * 10);
    }

    return $ret;
}


//用户与对方之间的关系（用户需要登录）
function s_user_ship($source, $target) {
    if (is_array($source)) {
        //用户数组
        $sid = $source['uid'];
    } else {
        $sid = $source;
    }

    if (is_array($target)) {
        //目标用户数组
        $tid = $target['uid'];
    } else {
        $tid = $target;
    }

    if (s_bad_id($sid)
        || s_bad_id($tid)
    ) {
        return false;
    }

    $data = array();
    $data['source_id'] = $sid;
    $data['target_id'] = $tid;

    $key = "user_friendship_by_tuid#{$tid}_{$sid}";

    if (false === ( $ret = s_memcache($key) )) {
        if ( false === ( $ret = s_weibo_http("https://api.weibo.com/2/friendships/show.json", $data) )) {
            return false;
        }

        //缓存中存储30秒
        s_memcache($key, $ret, 30);
    }

    return $ret;
}


//获取用户发布的微博列表
//  $uid                        用户主键
//  $type       forward, reply  返回的类别
//  $since                      微博主键大于此微博
//  $max                        微博主键小于此微博
//
function s_user_weibo_ids($uid, $type=0, $since=0, $max=0) {
    if (s_bad_id($uid)
        || s_bad_0id($max)
        || s_bad_0id($since)
        || s_bad_0id($type)
    ) {
        return false;
    }


    $page = 1;
    $ret  = array();

    while ($page > 0) {
        $key = 'user_weibo_forward_ids_by#'
            . 'uid='        . $uid
            . 'type='       . $type
            . 'since_id='   . $since
            . 'max_id='     . $max;

        if (false === ( $data = s_memcache($key) )) {
            $param = array(
                'uid'       => $uid,
                'since_id'  => $since_id,
                'max_id'    => $since_id,
                'page'      => $page,
                'feature'   => $type,
            );

            //if ( false === ( $data = s_weibo_http("http://i2.api.weibo.com/2/statuses/user_timeline/ids.json", $data) )) {
            if ( false === ( $data = s_weibo_http("https://api.weibo.com/2/statuses/user_timeline/ids.json", $data) )) {
                return false;
            }

            //缓存中存储30秒
            s_memcache($key, $data, 30);
        }

        $ret = array_merge($ret, $data['statuses']);

        $page = $data['next_cursor'];
    }


    return $ret;
}


//发送私信（内部接口，外部禁用）
//  必须用账号对应的appkey
//  发送私信时，appkey对应的账号必须登录
function s_user_message($uid, $message, $mid=false) {
    if (s_bad_id($uid)) {
        return false;
    }

    if (s_bad_string($message)) {
        return false;
    }

    $data = array();
    $data['uid']    = $uid;
    $data['text']   = $message;

    if (is_int($mid)) {
        $data['id'] = $mid;
    }


    return s_weibo_http("http://i2.api.weibo.com/2/direct_messages/new.json", $data, 'post');
}

